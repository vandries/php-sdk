<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \MusicStory\SDK\Context;
//use \MusicStory\SDK\Builder;
use \MusicStory\SDK\Signer;

/*Start connection to Music-Story's API*/
//$ms_api = new MS_API('Consumer Key', 'Consumer Secret', 'Access Token', 'Token Secret');
//$ms_api = new Context('7909715aa5389f132efb9e75c47587758d170125', 'Consumer Secret', '72ed79402dc594cc1c5c4606cd7dbf234e4b8569', 'c86b461e24f6ad509f56fca3323199045accc754');

require('MusicStoryAPI.class.php');


$ms_signer = new Signer(array('identifier'=>'7909715aa5389f132efb9e75c47587758d170125', 'secret'=>'111f1145f9791a395333577d743925eb9526d0ca'));
print_r($ms_signer->getSignature());

die;

$ms_api = new Context('7909715aa5389f132efb9e75c47587758d170125', '111f1145f9791a395333577d743925eb9526d0ca');
$api = new MusicStoryApi('7909715aa5389f132efb9e75c47587758d170125', '111f1145f9791a395333577d743925eb9526d0ca');

$ms_query = $ms_api->buildRequest();
$ms_query
    ->get()
    ->json()
    ->object('genre')
    ->id(5)
    //->connector('artiste', 'Main')
;

$ms_api->execute($ms_query);

echo $ms_query;

echo "\n";
echo "-------------------------------";
echo "\n";

$api->getGenre('5');
echo "\n";
/*
$ms_query = $ms_api->buildRequest();
$ms_query
    ->get()
    ->object('album')
    ->id(15436)
;

$ms_api->execute($ms_query);

echo $ms_query;
echo "\n";

$ms_query = $ms_api->buildRequest();
$ms_query
    ->json()
    ->get()
    ->object('album')
    ->idPartner('deezer', 40007)
;

$ms_api->execute($ms_query);

echo $ms_query;
echo "\n";

$ms_query = $ms_api->buildRequest();
$ms_query
    ->json()
    ->search()
    ->object('artist')
    ->filters(array('name' => 'bob'))
;

$ms_api->execute($ms_query);

echo $ms_query;
echo "\n";

$ms_query = $ms_api->buildRequest();
$ms_query
    ->get()
    ->object('artist')
    ->id(14568)
    ->connector('artist')
    ->filters(array('name' => 'bob'))
    ->fields(array('realname', 'main_role'))
;

$ms_api->execute($ms_query);

echo $ms_query;
echo "\n";
*/


die;


/*Start new Query*/
//$ms_query = $ms_api->newQuery($object , $type = null, $id = null);
//$object : 'GENRE', 'ARTIST', 'BIOGRAPHY'...etc
//$type : 'GET', 'SEARCH'
//$id : seulement si type == 'GET'

$ms_query->setLang($lang = 'fr');

$ms_query->setFilters($filters = array('firstname'=>'john', 'lastname'=>'lennon'));

/*Si type == 'GET'*/
$ms_query->setConnector($object = 'ARTISTS', $link = 'MAIN'); //passer un array(Object=>Link) ? pouvoir ainsi faire des requetes sur plus de 2 objets ?
$ms_query->setFields($fields = array('start_decade', 'end_decade', 'sex'));

echo $ms_query;

$xml_result = $ms_query->execute($num_page = '1');
//$num_page : numéro de la page souhaitée

//api.music-story.com/fr/genre/artists?filters=(firstname=john&lastename=lennon)&fields=(start_decade&end_decade&sex)&page=3&link=main

//prévoir une fonction 	protected function setKey($key, $val)

//&name=bob&page=4&link=Main&fields=start_decade%2Cend_decade