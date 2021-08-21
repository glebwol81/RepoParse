<?
// exit();
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/ext_www/yidy,jc,j.ru';
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/kggf.php");
 // header("Content-Type: text/html; charset=utf-8");
?>
<?CModule::IncludeModule("iblock");
CModule::IncludeModule("highloadblock");




$res = $strEntityDataClassProv::getList(array(
     // 'select' => ['ID'],
     'order' => array('UF_DATE' => 'ASC'),
     'filter' => ['UF_ACTIVE' => 1],
     // 'filter' => ['UF_COMMENT' => 'NO2 '],
     'limit' => 1000
));


while ($arItem = $res->fetch()) {
    // echo '<pre>';
    print_r($arItem['UF_LINK']);
    // $ids[] = $arItem['ID']; //UF_PROVIDER
    $arDomain = explode('/', $arItem['UF_LINK']);
    $domain = end($arDomain);
    // $domain = 'veronikakovach71';

    // echo end($arDomain)."\n";
    $params = array(
        // "domain" => end($arDomain),
        "filter" => 'owner',
        "count" => 100
    );
    // echo $arItem['ID'];

    if(substr_count($domain, 'club') > 0){
        $params['owner_id'] = str_replace('club', '-', $domain);
    }elseif(substr_count($domain, 'public') > 0){
        $params['owner_id'] = str_replace('public', '-', $domain);
    }else{
        $params['domain'] = $domain;
    }

    // print_r($params);
    // exit();
 // $t = $->method("market.get", $params_3);
    $result = $->method("wall.get", $params);
    // echo '<pre>';
    // print_r($result);
    // exit();
    
    $vkIds = [];
    $vkIds[] = $arItem['UF_ID_VK'];
    // echo "<pre style='text-align:left'>";
    // 
    // UF_COMMENT
    if($result->error){
    	 $strEntityDataClassProv::update($arItem['ID'], ['UF_DATE' => date('d.m.Y H:i:s'), 'UF_ACTIVE' => '', 'UF_COMMENT' => 'NO '.$result->error->error_msg]);
        echo $arItem['ID']."\n";
        continue;
    }

    
    foreach ($result->response->items as $key => $item) {
        $[] = $item->id;
        if($item->is_pinned == 1){//закрепленый
            continue;
        }

        if($item->date < time() - 60*60*24*7){
            continue;
        }

        if(!$item->attachments && !$item->text){
            continue;
        }

        if($item->id <= $arItem['UF_ID_VK']){
            continue;
        }

        $text = removeEmoji($item->text);

        $photos = [];
        foreach ($item->attachments as $attach) {
            $photo = '';
            if($attach->type == 'photo'){
                if($attach->photo->photo_807){
                    $photo = $attach->photo->photo_807;
                }elseif($attach->photo->photo_604){
                    $photo = $attach->photo->photo_604;
                }else{
                    $photo = $attach->photo->photo_130;
                }


                if(!$photo){
                    $arSizes = [];
                    foreach ($attach->photo->sizes as $size) {
                        $arSizes[$size->type] = $size->url;
                    }

                    if($arSizes['z']){
                        $photo = $arSizes['z'];
                    }elseif($arSizes['y']){
                        $photo = $arSizes['y'];
                    }else{
                        $photo = $arSizes['x'];
                    }
                    
                }
                
            }



            $photos[] = $photo;
        }

        // continue;
        // print_r($photos);
        // echo '<hr>';

        
        $arFields = [
            'UF_TEXT' => $text,
            'UF_IMAGE' => implode(';', $photos),
            'UF_PROVIDER' => $arItem['UF_PROVIDER'],
            'UF_ID_VK' => $item->id,
            'UF_LINK' => str_replace('m.', '', $arItem['UF_LINK']).'?w=wall'.$item->owner_id.'_'.$item->id

        ];
        
        $res2 = $strEntityDataClass::add($arFields);

        
        
        // exit();
    }
    //обновляем 
    $strEntityDataClassProv::update($arItem['ID'], [
            'UF_DATE' => date('d.m.Y H:i:s'), 
            'UF_ID_VK' => max($vkIds),
            'UF_ACTIVE' => 1, 
            'UF_COMMENT' => ''
        ]);

    echo $arItem['ID']."\n";



    usleep(400000);
}
