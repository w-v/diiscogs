<?php
ini_set('display_startup_errors',1); ini_set('display_errors',1); error_reporting(-1);
function getFromDb($query, $values)
{
	if(empty($values)) {
		//asking for fieds with a NULL value
		return;
	}
        //var_dump($values);
	//echo $query;
        try {
    include("../../own/ctl.inc");  
    $pdo = new PDO('mysql:host='.$host.';dbname='.$base.';charset=utf8', $username, $password);
    unset($base,$username,$host,$password);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ERRMODE_WARNING | ERRMODE_EXCEPTION | ERRMODE_SILENT
    //to prevent errors when passing int values (for offset and limit)
    $pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );
        $stmt = $pdo->prepare($query);
	$msc=microtime(true);
	global $finalQuery;
	array_push($finalQuery,$stmt->queryString);
	$stmt = bindInts($stmt,$values);
	/*$stmt->bindValue(':mn',0,PDO::PARAM_INT);
	$stmt->bindValue(':mx',25,PDO::PARAM_INT);
	$stmt->bindValue(':v0','dylan',PDO::PARAM_STR);*/
	//var_dump($stmt);
        $stmt->execute();
	$msc=microtime(true)-$msc;
	//echo ($msc*1000).' milliseconds';
        $result = $stmt->fetchAll();
//var_dump($result);
	return $result;

} catch(Exception $e) {
    echo "Impossible d'accéder?|  la base de données SQLite : ".$e->getMessage();
    die();
}
}
/* pdo's execute() treats all inputs as strings, this behaviour can be avoided by setting PDO::ATTR_EMULATE_PREPARES
 * to false but then a parameter can't be used multiple times in a query, and we need that. So we must bind 
 * values using PDO::PARAM_INT when they're ints
 */ 
function bindInts($stmt,$values){
	global $finalQuery;
	foreach($values as $k => $v){
		if(is_int($v)){
	//		echo("binding ".$v." k:".$k);
	//var_dump($values);
			
			$finalQuery[count($finalQuery)-1]=str_replace($k, $v, $finalQuery[count($finalQuery)-1]);
			$stmt->bindValue($k, $v, PDO::PARAM_INT);
		}
		else{
			$finalQuery = str_replace($k, "'".$v."'", $finalQuery);
			$stmt->bindValue($k, $v, PDO::PARAM_STR);
		}
		unset($values[$k]);
	}
	//var_dump($stmt);
	return $stmt;
}

function answer($query)
{
/*	$values=array('nm' => 'Bob Dylan');
        $query='SELECT * FROM artists WHERE name=:nm';
	return getFromDb($query, $values);}
*/
	global $a,$t,$v,$mn,$mx;
	switch($a){
		case 's': return searchFor($t,$v);break;
		case 'd': return display($t,$v);break;
		case 'p': return pres($t,$v);break;
	}
}
function pres($t,$v){
	switch($t){
		case '0': return '';break;
		case '1':
			$values=(array(':v' =>   'Love%', ':a' => '1975'));
			$query='SELECT masterID,title,year FROM masters WHERE year=:a AND title LIKE :v;';
			break;
		case '2':
			$values=(array(':v' =>   'Funk', ':a' => '1975'));
			$query='SELECT masters.masterID,title,artists.name as artist,year FROM masters,masters_styles,styles,masters_artists,artists WHERE masters.masterID=masters_artists.masterID and masters_artists.artistID=artists.artistID and masters.masterID=masters_styles.masterID AND masters_styles.styleID=styles.styleID AND year=:a AND style=:v group by masters.masterID';
			break;
		case '3':
			$values=array(':v' =>   'Funk', ':a' => '1975');
			$query='SELECT avg(a) as `average number of tracks per release` FROM (SELECT COUNT(*) as a from releases_tracks group by releaseID)a';
			break;
		case '4':
			$values=array(':v' =>   'Funk', ':a' => '1975');
			$query='SELECT count(*) as `number of masters`,year FROM masters,releases_masters WHERE masters.masterID=releases_masters.masterID group by masters.year;';
			break;
		case '5':
			$values=(array(':a' =>   '1124', ':b' => '77',':c' => '215'));
			$query='select masters.masterID,masters.title,masters.year,artists.name from masters,releases_masters,artists,releases_artists,releases_extraartists as a,releases_extraartists as b where masters.masterID=releases_masters.masterID and releases_masters.releaseID=releases_artists.releaseID and artists.artistID=releases_artists.artistID and releases_artists.releaseID=a.releaseID and a.roleID=:a and b.roleID=:b and a.releaseID=b.releaseID and a.releaseID not in (select c.releaseID from releases_extraartists as c where c.roleID=:c) group by masters.masterID order by year ASC;';
			break;
		case '6':
			$values=(array(':a' =>   '1970', ':b' => '1990'));
			$query='select artists.artistID,artists.name from artists where not exists (select year from (select year from masters where year>:a and year<:b group by year)a where not exists (select masters.masterID from masters_artists,masters where masters_artists.masterID=masters.masterID and masters.year=a.year and masters_artists.artistID=artists.artistID));';
			break;
	}
	return getFromDb($query,$values);
}
function display($t,$v){
	global $a,$t,$v,$mn,$mx,$values;
	$query;
	switch($t) {
		case 'a':
			$json;
			//unset $values['mn'];
			//unset $values['mx'];
			$values=(array(':v' =>  $v));
			//$query='select artists.name as main, realname, profile, (select group_concat(artists_aliases.name) from artists_aliases where artists_aliases.artistID=artists.artistID) as aliases, (select group_concat(artists_namevariations.name) from artists_namevariations where artists_namevariations.artistID=artists.artistID) as `name variations`,(select group_concat(url) from artists_urls where artists_urls.artistID=artists.artistID) as links, data_quality as `data quality` from artists,data_quality where artists.artistID=:v and data_quality.data_qualityID = artists.data_qualityID';
			$query='select artists.artistID, artists.name , realname, profile, data_quality as `data quality` from artists,data_quality where artists.artistID=:v and data_quality.data_qualityID = artists.data_qualityID';
			$json = getFromDb($query,$values)[0];

			//var_dump($json);
			$query='select artists_aliases.name as alias from artists_aliases,artists where artists_aliases.artistID=artists.artistID and artists.artistID=:v';
			$json['aliases']= getFromDb($query,$values);

			$query='select artists_namevariations.name as `name variation` from artists_namevariations,artists where artists_namevariations.artistID=artists.artistID and artists.artistID=:v';
			$json['name variations']= getFromDb($query,$values);

			$query='select url from artists_urls,artists where artists_urls.artistID=artists.artistID and artists.artistID=:v';
			$json['urls']= getFromDb($query,$values);

			$query='select artists.artistID, artists.name from artists,artists_members where artists.artistID = artists_members.artistID_group and artists_members.artistID_member=:v';
			$json['in groups']= getFromDb($query,$values);

			$query='select artists.artistID, artists.name from artists,artists_members where artists.artistID = artists_members.artistID_member and artists_members.artistID_group=:v';
			$json['members']= getFromDb($query,$values);

			$json['related']= array();
			$query='select masters.masterID,masters.title,masters.year from masters,masters_artists where masters.masterID=masters_artists.masterID and masters_artists.artistID=:v ORDER BY year ASC';
			$x=getFromDb($query,$values);
			if(!empty($x)){
				$json['related']['masters'] = $x;
			}
			$query='select masters.masterID,masters.title,masters.year,artists.artistID,artists.name as artist,role.role from masters,masters_artists,artists,masters_main_release,releases_extraartists,role where releases_extraartists.roleID=role.roleID and masters_artists.artistID = artists.artistID and masters.masterID=masters_artists.masterID and masters_main_release.masterID=masters.masterID and masters_main_release.releaseID_main=releases_extraartists.releaseID and releases_extraartists.artistID=:v ORDER BY year ASC';
			$x=getFromDb($query,$values);
			if(!empty($x)){
				//var_dump($x);
				$x=array_map(function($k) use(&$x){ 
					//var_dump($k);
					$tmp=array('artist' => array('artistID' => $x[$k]['artistID'],'name' => $x[$k]['artist']));

					unset($x[$k]['artistID']);
					unset($x[$k]['artist']);
					return array_merge(array_splice($x[$k],0,2),$tmp,array_splice($x[$k],0,count($x)));
				},array_keys($x));
				$json['related']['extra'] = $x;
			}
			//$query='select (select count(*) from (select artistID from releases_artists,releases_masters where artistID=:v and releases_artists.releaseID=releases_masters.releaseID limit 1)d) as releases,(select count(*) from (select artistID from masters_artists where artistID=:v limit 1)e) as masters, (select count(*) from (select artistID from releases_extraartists,releases_masters where artistID=:v releases_extraartists.releaseID=releases_masters.releaseID limit 1)e) as `releases extra` (select count(*) from (select artistID from releases_extraartists,releases_masters,releases,releases_tracks where releases_tracks_artists.artistID=:v releases_tracks_artists.trackID=releases_tracks.trackID and releases_tracks.releaseID=releases.releaseID and releases.releaseID=releases_masters.releaseID limit 1)e) as `releases tracks`';
			//$json['members']= getFromDb($query,$values);
			return $json;
			break;
		case 'm':
			$json;
			$values=(array(':v' =>  $v));
			$query='select artists.artistID,artists.name from masters,masters_artists,artists where masters.masterID=masters_artists.masterID and masters_artists.artistID=artists.artistID and masters.masterID=:v';
			$json['artists']= getFromDb($query,$values);
			$query='select masters.masterID,masters.title ,masters.year,data_quality as `data quality` from masters,data_quality where masters.data_qualityID=data_quality.data_qualityID and masters.masterID=:v';
			//,masters_artists,artists where masters.masterID=masters_artists.masterID and masters_artists.artistID=artists.artistID
			$aac=getFromDb($query,$values)[0];
			$json = array_merge($json,$aac);
			$tmp=$json['masterID'];
			unset($json['masterID']);
			$json=array_merge(array('masterID' => $tmp),$json);
			$query='select genres.genre from masters,masters_genres,genres where masters.masterID=masters_genres.masterID and masters_genres.genreID=genres.genreID and masters.masterID=:v';
			$json['genres']= getFromDb($query,$values);
			$query='select styles.style from masters,masters_styles,styles where masters.masterID=masters_styles.masterID and masters_styles.styleID=styles.styleID and masters.masterID=:v';
			$json['styles']= getFromDb($query,$values);
			$query='select masters_videos.src from masters,masters_videos where masters.masterID=masters_videos.masterID and masters.masterID=:v';
			$json['videos']= getFromDb($query,$values);
	//		$query='select masters_main_release.releaseID_main as releaseID from masters_main_release,masters where masters.masterID=masters_main_release.masterID and masters.masterID=:v';
	//		$json['main release']= getFromDb($query,$values);
			$json['related']= array();
			$query='select releases_tracks.trackID,releases_tracks.title,releases_tracks.position,duration from masters_main_release,masters,releases_tracks where masters.masterID=masters_main_release.masterID and releases_tracks.releaseID=masters_main_release.releaseID_main and masters.masterID=:v';
			$x=getFromDb($query,$values);
			if(!empty($x)){
				$json['related']['tracks'] = $x;
			}
			$query='select releases.releaseID,releases.title,country,releasedate as `release date` from releases_masters,masters,releases,countries where masters.masterID=releases_masters.masterID and releases.releaseID=releases_masters.releaseID and releases.countryID=countries.countryID and masters.masterID=:v ORDER BY releasedate ASC';
			$x=getFromDb($query,$values);
			if(!empty($x)){

				$json['related']['releases'] = $x;
			}
			
			return $json;
			break;
		case 'r':
			$json;
			$values=(array(':v' =>  $v));
			$query='select releases.releaseID, releases.title as name, country, releasedate as `release date`, status,data_quality as `data quality` from releases,data_quality,countries,status where releases.countryID=countries.countryID and releases.statusID=status.statusID and releases.releaseID=:v and data_quality.data_qualityID = releases.data_qualityID';
			$json = getFromDb($query,$values)[0];

			$query='select releases_artists.artistID,artists.name from releases,releases_artists,artists where releases.releaseID=releases_artists.releaseID and releases_artists.artistID=artists.artistID and releases.releaseID=:v';
			$json['artists']= getFromDb($query,$values);

			$query='select releases_masters.masterID,masters.title as `title` from releases_masters,releases,masters where releases_masters.releaseID=releases.releaseID and releases_masters.masterID=masters.masterID and releases.releaseID=:v';
			$json['masters']= getFromDb($query,$values);
			//is only 1 master per release but it has to be wrapped in something as the script is, no time to fix it

			$query='select format from releases_formats,format,releases where releases.releaseID=releases_formats.releaseID and releases_formats.formatID=format.formatID and releases.releaseID=:v';
			$json['formats']= getFromDb($query,$values);

			$query='select identifier from releases_identifiers,identifiers,releases where releases.releaseID=releases_identifiers.releaseID and releases_identifiers.identifierID=identifiers.identifierID and releases.releaseID=:v';
			$json['identifiers']= getFromDb($query,$values);

			$query='select releases_videos.src from releases,releases_videos where releases.releaseID=releases_videos.releaseID and releases.releaseID=:v';
			$json['videos']= getFromDb($query,$values);

			$json['related']= array();
			$query='select releases_tracks.trackID,releases_tracks.title,releases_tracks.position,duration from releases,releases_tracks where releases_tracks.releaseID=releases.releaseID and releases.releaseID=:v';
			$x=getFromDb($query,$values);
			if(!empty($x)){
				$json['related']['tracks'] = $x;
			}
			return $json;
		default:
			break;
	}
}
function searchFor($t,$v){
	global $a,$t,$v,$mn,$mx,$values;
	$query;
	$values=(array(':mn' =>  (int) $mn,':mx' =>  (int) $mx-$mn));
	//return $v;
	switch ($t) {
		case 'a': 
			$f=array('name','realname');//,'realname');
			$query='select artistID, name ,realname from artists_name_FTS where '.makeFTSquery($f,$v).'  limit :mx offset :mn;';
			//$values=(array(':mn' => $mn,':mx' => $mx-$mn, ':v0' => 'dylan', ':v1' => '%bob%'));
			//$query='select name,realname from artists_name_FTS where match (name,realname) against (:v0 in boolean mode) AND ( name LIKE :v1 or realname like :v1 ) limit :mx offset :mn;';
			//$query="select artistID,name,realname from artists_name_FTS where match(name,realname) against('ed*' in boolean mode) LIMIT :mx offset :mn;";
			return getFromDb($query,$values);
			break;
		default:
			//return array("qsd" =>  "shithead");
			break;
	}
}
/*function makeSearchQuery($t,$v){
	$f=$searchFields[$t];
	$type=$types[$t]
	$tables=array_map(function($e){ 
		global $type;
		return $type.'_'.$e;
	},$searchTables[$t]);
	$join=array_map(function($e) ,$tables)
	return 
}*/
/* because the minimum length of a string that can be used in a match against is defined in the mysql server config
 * which I can not access, have to match against for longer words and do a LIKE for shorter words.
 * nb: match against are faster
 */
function makeFTSquery($f,$q){
	global $values;
	//var_dump($values);
        $a=explode(' ',$q);
	//var_dump($a);
	/* sorting the array of words by length so that if a match against can be made, it is made first. And the like
	 * , if there's one, is made on the resulting set which is smaller, thus reducing query time
	 */
	usort($a,'st');
	//var_dump($a);
	$r=array();
        for($i=0;$i<sizeof($a);$i++){
		$b=$a[$i];
		if(strlen($b) < 4){
			array_push($r,multipleFieldsFTS($f,$i));
			$values['v'.$i] = "%".$b."%";
		}
		else {	
			array_push($r,"match (".implode(',',$f).") against (:v".$i." in boolean mode)");
			$values['v'.$i] = $b.'*';
		}
        }
        return implode(' AND ', $r);
}
function st($a,$b){
    return strlen($b)-strlen($a);
}
function multipleFieldsFTS($f,$i){
	$h=array();
	foreach($f as $g){
		array_push($h,$g." LIKE :v".$i);
	}
	return '('.implode(' OR ', $h).')';
}
function makeXML($a){
	global $types,$t;
	$root=$types[$t];
	$field=substr($root,0,-1);
	if(isset($a[0])){
		$e=$a;
	}else{
		$e=array($field => $a);
	}
	return makeTag($root,$e);
}
function getID(&$a,&$field){
	$kidA=filterBy(array_keys($a),'ID');
	if(sizeof($kidA) > 0){
		$kid=$kidA[0];
		$field=substr($kid,0,-2);
		$id=$a[$kid];
		unset($a[$kid]);
		return ' id="'.$id.'"';
	}else{
		return '';
	}
	//pas beau

}
function makeTag($name,$content){
	$name=str_replace(' ','',$name);
	$z;
	$id='';
	$field='';
	if($content == null || $content == ''){
		return '';
	}
	if(is_array($content)){
		$id=getID($content,$field);
		if(is_numeric($name)){
			if($id==''){
				reset($content);
				$name=key($content);
				return makeTag($name,$content[$name]);
				//ouuh c'est moche
			}else{
				$name=$field;
			}
		}
		$z=implode(array_map(function($k) use(&$content){ return makeTag($k,$content[$k]);},array_keys($content)));
	} else {
		$z=$content;
	}
	return '<'.$name.$id.'>'.$z.'</'.$name.'>';
}
/*function makeTags(&$a){
	return implode(array_map(function($k) use(&$a){ return makeTag($k,$a[$k]);},array_keys($a)));
}*/
function filterBy($a,$str){
        return array_filter($a,function($k) use(&$str){ return strpos($k,$str) !== false;});
}
//$query=$_GET;
$query=$_POST;
$tables = array('genres','styles','data_quality','entities','jn','role','countries','status','identifiers','format','artists','artists_aliases','artists_members','artists_namevariations','artists_urls','labels','labels_urls','labels_sublabels','masters','masters_artists','masters_genres','masters_styles','masters_videos','releases','releases_artists','releases_genres','releases_styles','releases_companies','releases_extraartists','releases_formats','releases_identifiers','releases_tracks','releases_tracks_artists','releases_tracks_extraartists','releases_videos');
$values;
$finalQuery=array();
$a=$query['a'];
$t=$query['t'];
$v=$query['v'];
if(isset($query['mn'])){
	$mn=(int) $query['mn'];
	$mx=(int) $query['mx'];
}
$types=array(
	'a' => 'artists',
	'm' => 'masters',
	'r' => 'releases'
);
/*$searchFields=array(
	'a' => array('name','realname')
);*/
$json=answer($query);
//array_walk_recursive($json, function(&$v){ $v = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');});
//var_dump($json);
//echo htmlspecialchars(makeXML($json));//makeTag('as',array("a" => "b")));
//$w='a'
//$x=makeTag($w,$w);
//$w=array("rID" => "2","a" => "b");
//$x=makeTag('as',$w);
//$x=makeTag(array("b" => "c"),'as');
//echo htmlspecialchars($x);
//var_dump(filterBy($w,'ID'));
//var_dump($json);
if($a == 'p'){
	echo json_encode(array($finalQuery, $json));
}
else{
	echo json_encode(array($finalQuery, array(htmlspecialchars(makeXML($json))), $json));
}
//echo array($finalQuery, $json);
//echo json_encode($_POST['q']);
?>

