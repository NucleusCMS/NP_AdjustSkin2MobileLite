<?php

/* * * * * * * * * * * * * * * * * * * * * * *
 * NP_AdjustSkin2MobileLite 0.1
 * 携帯端末 スマートフォン用Skinへ振り分けを行う
 * NP_AdjustSkin2Mobile.php - 2009/10/27 2011/3/10
 * Copyright (C) 2009-2011 Nakazoe
 * nakazoe@comiu.com
 * * * * * * * * * * * * * * * * * * * * * * */
class NP_AdjustSkin2MobileLite extends NucleusPlugin{
var $CarrerName;
var $CarrierShortName;
var $SkinName;
var $isStarted;
var $RoundWidth;
var $PictDir;

function getName(){              return 'AdjustSkin2MobileLite';}
function getAuthor(){            return 'Nakazoe';}
function getURL(){               return 'mailto:nakazoe@comiu.com';}
function getVersion(){           return '0.1';}
function getMinNucleusVersion(){ return '341';}
function getDescription(){       return '携帯/スマートフォン端末に適したSkinへ振り分けをおこないます。';}


function getEventList(){
  return array(
    'InitSkinParse',
    'PreSkinParse',
    'PostSkinParse',
    'PreSendContentType',
    'PreAddComment',
    'PostAuthentication'
  );
}


function install(){
  $this->createOption(
    'as2m_sp_pri',
    'スマートフォン対応SKINプライオリティ',
    'select',
    '1',
    'SmartPhone > default|1|SmartPhone > Mobile > default|2'
  );

  global $CONF; 
  $PictDir = $CONF['IndexURL']."nucleus/plugins/adjustskin2mobilelite/images/";

  $this->createOption(
    'as2m_pict_path',
    '絵文字画像へのパス',
    'text',
    $PictDir
  );

  $this->createOption(
    'as2m_mobile_name',
    'ケータイスキン名',
    'text',
    'mobile'
  );

  $this->createOption(
    'as2m_smartphone_name',
    'スマートフォンスキン名',
    'text',
    'sphone'
  );


  $this->createOption(
    'as2m_docomo_name',
    'docomoスキン名',
    'text',
    'docomo'
  );

  $this->createOption(
    'as2m_softbank_name',
    'SoftBankスキン名',
    'text',
    'softbank'
  );

  $this->createOption(
    'as2m_au_name',
    'auスキン名',
    'text',
    'ezweb'
  );

  $this->createOption(
    'as2m_wilcom_name',
    'Wilcomスキン名',
    'text',
    'willcom'
  );

  $this->createOption(
    'as2m_iphone_name',
    'iPhoneスキン名',
    'text',
    'iphone'
  );

  $this->createOption(
    'as2m_android_name',
    'Androidスキン名',
    'text',
    'android'
  );








  
}



function supportsFeature($feature) {
  switch($feature) {
    case 'SqlTablePrefix':
      return 1;
    case 'HelpPage':
      return 1;
    default:
    return 0;
  }
}

function doIf($key=null, $value=null) {
if(strcmp($key,"CarrierName")==0){
  if(strcmp($this->CarrierName,$value)==0){
    return true;
  }
}else{
  return false;
}
}

function Init(){
  $this->PictDir = $this->getOption('as2m_pict_path');
  $this->RoundWidth();
}

function event_PreAddComment(&$data) {
  if($this->isMobile()){
    $data['comment']['user'] = $contents = mb_convert_encoding($data['comment']['user'], _CHARSET, 'sjis-win');
    $data['comment']['body'] = $contents = mb_convert_encoding($data['comment']['body'], _CHARSET, 'sjis-win');
  }
}

function event_PostAuthentication($data) {
  global $CONF;
  if ($this->isMobile() && !$CONF['UsingAdminArea']) {
    if (requestVar('action')=='addcomment' || strlen(getVar('query'))) {
      // check if valid SJIS
      if (!encoding_check(false,false,'Shift_JIS')) {
        foreach(array($_REQUEST, $_SERVER) as $input) {
           array_walk($input, 'encoding_check');
        }
      }
      // user/body/query won't be checked anymore.
      encoding_check(false,false,false,array('user','body','query'));
    }
  }
}

//ContentType docomoはxhtm指定
function event_PreSendContentType(&$data){
if($this->isMobile()){
  $data['charset'] = 'Shift_JIS';
  if($this->isDoCoMo()){
    $data['contentType'] = 'text/html';
  }else{
    $data['contentType'] = 'application/xhtml+xml';
  }
}else{
  $data['charset'] = 'UTF-8';
  $data['contentType'] = 'text/html';
}
return;
}

//Skinパース前　パース終了までの間にdoConvertを通す
function event_PreSkinParse(&$data) {
$this->HtmlHeader();
  // キャッシュはさせない
  header('Pragma: no-cache');
  header('Cache-Control: no-cache, must-revalidate');
  ob_implicit_flush(false);
  $this->isStarted = ob_start(array(&$this, 'doConvert'));

}

//Skinパース後
function event_PostSkinParse($data) {
if ($this->isStarted) {
  ob_end_flush();
}
}

//ページの全要素に対して絵文字のコンバートと文字コードの変更
function doConvert($strHTML) {
$strHTML = mb_convert_encoding($strHTML, 'SJIS-win', _CHARSET);

if($this->isEZweb()){
  $strHTML = $this->iemojiEncode($strHTML,1);
}elseif($this->isVodafone()){
  $strHTML = $this->iemojiEncode($strHTML,2);
}elseif(!$this->isMobile()){
  $strHTML = $this->iemojiEncode($strHTML,3);
  $strHTML = mb_convert_encoding($strHTML, _CHARSET, "SJIS-win");
}
return $strHTML;
}


//Skinのパース時　適用するSkinの切り替え
function event_InitSkinParse(&$data){
  $DefaultPCSkinName_str     = $data['skin']->name;
  $DefaultMobileSkinName_str = $data['skin']->name."/".$this->getOption('as2m_mobile_name');
  $DefaultSPSkinName_str     = $data['skin']->name."/".$this->getOption('as2m_smartphone_name');

  //Mobile
  if($this->isMobile() && SKIN::exists($DefaultMobileSkinName_str)){
    $DefaultSkinName = $DefaultMobileSkinName_str;
  }elseif($this->isMobile() && !SKIN::exists($DefaultMobileSkinName_str)){
    $DefaultSkinName = $DefaultPCSkinName_str;
  }

  //SmartPhone
  if($this->isSmartPhone() && !SKIN::exists($DefaultSPSkinName_str)){
    //1=>SmartPhone > default
    if($this->getOption('as2m_sp_pri') == 1){
      $DefaultSkinName = $DefaultPCSkinName_str;
    //2=>SmartPhone > Mobile > default
    }elseif($this->getOption('as2m_sp_pri') == 2){
      if(SKIN::exists($DefaultMobileSkinName_str)){
        $DefaultSkinName = $DefaultMobileSkinName_str;
      }else{
        $DefaultSkinName = $DefaultPCSkinName_str;
      }
    }
  }elseif($this->isSmartPhone() && SKIN::exists($DefaultSPSkinName_str)){
    $DefaultSkinName = $DefaultSPSkinName_str;
  }

  //other
  if(!$this->isSmartPhone() && !$this->isMobile()){
    $DefaultSkinName = $DefaultPCSkinName_str;
  }

  if($this->isDoCoMo()){
    $CarrierSkinName = $data['skin']->name."/".$this->getOption('as2m_docomo_name');
    $this->CarrierName = "docomo";
    $this->CarrierShortName = "d";

  }elseif($this->isVodafone()){
    $CarrierSkinName = $data['skin']->name."/".$this->getOption('as2m_softbank_name');
    $this->CarrierName = "softbank";
    $this->CarrierShortName = "s";

  }elseif($this->isEZweb()){
    $CarrierSkinName = $data['skin']->name."/".$this->getOption('as2m_au_name');
    $this->CarrierName = "ezweb";
    $this->CarrierShortName = "e";

  }elseif($this->isWillcom()){
    $CarrierSkinName = $data['skin']->name."/".$this->getOption('as2m_wilcom_name');
    $this->CarrierName = "willcom";
    $this->CarrierShortName = "w";

  }elseif($this->isiPhone()){
    $CarrierSkinName = $data['skin']->name."/".$this->getOption('as2m_iphone_name');
    $this->CarrierName = "iphone";
    $this->CarrierShortName = "ip";

  }elseif($this->isAndroid()){
    $CarrierSkinName = $data['skin']->name."/".$this->getOption('as2m_android_name');
    $this->CarrierName = "android";
    $this->CarrierShortName = "an";

  }else{
    $this->CarrierName = "pc";
  }

  //CarrierSkin > DefaultSkin
  if(SKIN::exists($CarrierSkinName)){
    $SkinName = $CarrierSkinName;
  }else{
    $SkinName = $DefaultSkinName;
  }


  $skin =& SKIN::createFromName($SkinName);
  $data['skin']->SKIN($skin->getID());

  return;
}

//携帯が使用できる画面幅のROUND
function RoundWidth(){
  if($this->isMobile()){
    $this->RoundWidth = 240;
  }else{
    $this->RoundWidth = 480;
  }
  return;
}

//スキン変数
function doSkinVar(&$item,$key=null,$name=null){

  if(strcmp($key,"isMobile")==0){
    print $this->isMobile();

  }elseif(strcmp($key,"isiPhone")==0){
    print $this->isiPhone();

  }elseif(strcmp($key,"isAndroid")==0){
    print $this->isAndroid();

  }elseif(strcmp($key,"CarrierName")==0){
    print $this->CarrierName;

  }elseif(strcmp($key,"RoundWidth")==0){
    print $this->RoundWidth;
  }
}

//テンプレート変数
function doTemplateVar(&$item,$key=null){
  if(strcmp($key,"isMobile")==0){
    print $this->isMobile();

  }elseif(strcmp($key,"isiPhone")==0){
    print $this->isiPhone();

  }elseif(strcmp($key,"isAndroid")==0){
    print $this->isAndroid();

  }elseif(strcmp($key,"CarrierName")==0){
    print $this->CarrierName;

  }elseif(strcmp($key,"RoundWidth")==0){
    print $this->RoundWidth;
  }
}



function Platform(){
	$UA = explode("/",$_SERVER['HTTP_USER_AGENT']);

	// DoCoMo
	if ($UA[0] == 'DoCoMo') {
		$phone =  array("Platform" => "DoCoMo","PlatformFlg" => 1);

	// au
	// EZweb 旧端末用
	}elseif (!preg_match('@^KDDI@',"$UA[0]") && $UA[0] == 'UP.Browser') {
		$phone = array("Platform" => "EZweb","PlatformFlg" => 2);

	// EZweb WAP2.0 対応端末
	}elseif (preg_match('@^KDDI@',"$UA[0]")) {
		$phone = array("Platform" => "AU","PlatformFlg" => 2);

	// SoftBank
	// Vodafone
	}elseif (preg_match('@^Vodafone@',"$UA[0]")){
		$phone = array("Platform" => "Vodafone","PlatformFlg" => 3);

	// J-PHONE 
	}elseif (preg_match('@^J-PHONE@',"$UA[0]")) {
		$phone = array("Platform" => "J-PHONE","PlatformFlg" => 3);

	// SoftBank
	}elseif (preg_match('@^SoftBank@',"$UA[0]")){
		$phone = array("Platform" => "SoftBank","PlatformFlg" => 3);

	// 上記以外
	}elseif ($UA[0] == 'ASTEL')  {
		$phone = array("Platform" => "astel","PlatformFlg" => 4); // ドットi

	}elseif ($UA[0] == 'L-mode') {
		$phone = array("Platform" => "Lmode","PlatformFlg" => 5); // L-mode

	}elseif ($UA[0] == 'PDXGW')  {
		$phone = array("Platform" => "H\"","PlatformFlg" => 6);   // H"



	// willcom
	}elseif (preg_match('/^Mozilla.+(DDIPOCKET|WILLCOM)/', $_SERVER['HTTP_USER_AGENT'])) {
		$phone = array("Platform" => "Willcom","PlatformFlg" => 7);
	//emobile
	}elseif (preg_match('/^emobile/', $_SERVER['HTTP_USER_AGENT'])) {
		$phone = array("Platform" => "emobile","PlatformFlg" => 8);


	// iPhone
	}elseif(preg_match("/iPhone/",$_SERVER['HTTP_USER_AGENT'])){
		$phone = array("Platform" => "iPhone","PlatformFlg" => 10);

	// iPod Touch
	}elseif(preg_match("/iPod/",$_SERVER['HTTP_USER_AGENT'])){
		$phone = array("Platform" => "iPhone","PlatformFlg" => 11);

	//android
	}elseif(preg_match("/Android/",$_SERVER['HTTP_USER_AGENT'])){
		$phone = array("Platform" => "Android","PlatformFlg" => 12);
	//blackberry
	}elseif(preg_match("/blackberry/",$_SERVER['HTTP_USER_AGENT'])){
		$phone = array("Platform" => "BlackBerry","PlatformFlg" => 13);

	//BOT系
	//google
	}elseif (preg_match('/Googlebot-Mobile/', $_SERVER['HTTP_USER_AGENT'])) {
		$phone = array("Platform" => "MobileBot","PlatformFlg" => 21);
	//yahoo
	}elseif (preg_match('/Y!J/', $_SERVER['HTTP_USER_AGENT'])) {
		$phone = array("Platform" => "MobileBot","PlatformFlg" => 21);

	// それ以外 PC扱い
	}else {
		$phone = array("Platform" => "pc","PlatformFlg" => 9);
	}
	return $phone;
}


function isDoCoMo(){
	$PlatForm_ary = $this->Platform();
	if($PlatForm_ary["PlatformFlg"] == 1){
		return true;
	}else{
		return false;
	}
}

function isEZweb(){
	$PlatForm_ary = $this->Platform();
	if($PlatForm_ary["PlatformFlg"] == 2){
		return true;
	}else{
		return false;
	}
}

function isVodafone(){
	$PlatForm_ary = $this->Platform();
	if($PlatForm_ary["PlatformFlg"] == 3){
		return true;
	}else{
		return false;
	}
}

function isWillcom(){
	$PlatForm_ary = $this->Platform();
	if($PlatForm_ary["PlatformFlg"] == 7){
		return true;
	}else{
		return false;
	}
}


function isiPhone(){
	$PlatForm_ary = $this->Platform();
	if(
		$PlatForm_ary["PlatformFlg"] == 10 || 
		$PlatForm_ary["PlatformFlg"] == 11
		
	){
		return true;
	}else{
		return false;
	}
}

function isAndroid(){
	$PlatForm_ary = $this->Platform();
	if($PlatForm_ary["PlatformFlg"] == 12){
		return true;
	}else{
		return false;
	}
}

function isSmartPhone(){
	if(
		$this->isiPhone() ||
		$this->isAndroid()
	){
		return true;
	}else{
		return false;
	}
}


function isMobile(){
	$PlatForm_ary = $this->Platform();
	if(
		$PlatForm_ary["PlatformFlg"] == 1  || 
		$PlatForm_ary["PlatformFlg"] == 2  || 
		$PlatForm_ary["PlatformFlg"] == 3  || 
		$PlatForm_ary["PlatformFlg"] == 7
	){
		return true;
	}else{
		return false;
	}
}






//ヘッダの書き出し
function HtmlHeader(){
	if($this->isDoCoMo()){
		header("Content-Type:application/xhtml+xml; charset=Shfit_JIS");
	}elseif($this->isEZweb()){
		header("Content-Type:text/html; charset=Shfit_JIS");
	}elseif($this->isVodafone()){
		header("Content-Type:text/html; charset=Shfit_JIS");
		//header('x-jphone-copyright: no-store,no-transfer,no-peripheral');
	}elseif($this->isWillcom()){
		header("Content-Type:text/html; charset=Shfit_JIS");
	}else{
		header("Content-type: text/html; charset=UTF-8");
	}
}

//絵文字のコンバート
function iemojiEncode ( $str, $Platform = 2 , $opt = true ){
  $str = unpack("C*", $str);
  $len = count($str);
  $buff = "";
  $n = 1;

  //変換表を作成
  $pictAry = $this -> AryPict();
  while($n <= $len) {
    $ch1 = $str[$n];
    $ch2 = $str[$n+1];
    if((($ch1 == 0xF8)  && (0x9F <= $ch2) && ($ch2 <= 0xFC)) ||
      (($ch1 == 0xF9) &&
      ((0x40 <= $ch2) && ($ch2 <= 0x49) ||
      (0x50 <= $ch2)  && ($ch2 <= 0x52) ||
      (0x55 <= $ch2)  && ($ch2 <= 0x57) ||
      (0x5B <= $ch2)  && ($ch2 <= 0x5E) ||
      (0x72 <= $ch2)  && ($ch2 <= 0x7E) ||
      (0x80 <= $ch2)  && ($ch2 <= 0xB0)))) {
      if($opt)
        $code = strval(($ch1 << 8) + $ch2);
        $code = dechex ($code);
        $code = strtoupper($code);

        if(!$this->isMobile()){
          $buff .= '<img src="'.$this->PictDir.$pictAry[$code][$Platform].'.gif" />';

        }elseif($this->isEZweb() && is_numeric($pictAry[$code][$Platform])) {
          $buff .= mb_convert_encoding('<img src="" alt="" localsrc="'.$pictAry[$code][$Platform].'" />', 'SJIS-win', _CHARSET);
        }else{
          $buff .= mb_convert_encoding($pictAry[$code][$Platform], 'SJIS-win', _CHARSET);
        }

      $n++;
    }
    elseif(($ch1 == 0xF9) && (0xB1 <= $ch2) && ($ch2 <= 0xFC)) {
      if($opt)
        $code = strval(($ch1 << 8) + $ch2);
        $code = dechex ($code);
        $code = strtoupper($code);

        if(!$this->isMobile()){
          $buff .= '<img src="'.$this->PictDir.$pictAry[$code][$Platform].'.gif" />';

        }elseif($this->isEZweb() && is_numeric($pictAry[$code][$Platform])) {
          $buff .= mb_convert_encoding('<img src="" alt="" localsrc="'.$pictAry[$code][$Platform].'" />', 'SJIS-win', _CHARSET);

        }else{
          $buff .= mb_convert_encoding($pictAry[$code][$Platform], 'SJIS-win', _CHARSET);
        }

      $n++;
    }
    // 2バイト文字の処理
    elseif(((0x81 <= $ch1) && ($ch1 <= 0x9f) ) || ((0xe0 <= $ch1) && ($ch1 <= 0xfc))){
      $buff .= pack("C", $ch1) . pack("C", $ch2);
      $n++;
    }
    else
      $buff .= pack("C", $ch1);
    $n++;
  }

  return $buff;
}

//キャリア毎の絵文字対応表　0 docomo 1 au 2 Softbank 3 PC
function AryPict(){
  $aryPict['F89F'] = array(0 => 'E63E',  1 => 44,           2 => '$Gj',       3 => 1);    //　晴れ             
  $aryPict['F8A0'] = array(0 => 'E63F',  1 => 107,          2 => '$Gi',       3 => 2);    //　曇り             
  $aryPict['F8A1'] = array(0 => 'E640',  1 => 95,           2 => '$Gk',       3 => 3);    //　雨               
  $aryPict['F8A2'] = array(0 => 'E641',  1 => 191,          2 => '$Gh',       3 => 4);    //　雪               
  $aryPict['F8A3'] = array(0 => 'E642',  1 => 16,           2 => '$E]',       3 => 5);    //　雷               
  $aryPict['F8A4'] = array(0 => 'E643',  1 => 190,          2 => '$Pc',       3 => 6);    //　台風             
  $aryPict['F8A5'] = array(0 => 'E644',  1 => 305,          2 => '[霧]',        3 => 7);    //　霧               
  $aryPict['F8A6'] = array(0 => 'E645',  1 => 481,          2 => '$P\',       3 => 8);    //　小雨             
  $aryPict['F8A7'] = array(0 => 'E646',  1 => 192,          2 => '$F_',       3 => 9);    //　牡羊座           
  $aryPict['F8A8'] = array(0 => 'E647',  1 => 193,          2 => '$F`',       3 => 10);   //　牡牛座           
  $aryPict['F8A9'] = array(0 => 'E648',  1 => 194,          2 => '$Fa',       3 => 11);   //　双子座           
  $aryPict['F8AA'] = array(0 => 'E649',  1 => 195,          2 => '$Fb',       3 => 12);   //　蟹座             
  $aryPict['F8AB'] = array(0 => 'E64A',  1 => 196,          2 => '$Fc',       3 => 13);   //　獅子座           
  $aryPict['F8AC'] = array(0 => 'E64B',  1 => 197,          2 => '$Fd',       3 => 14);   //　乙女座           
  $aryPict['F8AD'] = array(0 => 'E64C',  1 => 198,          2 => '$Fe',       3 => 15);   //　天秤座           
  $aryPict['F8AE'] = array(0 => 'E64D',  1 => 199,          2 => '$Ff',       3 => 16);   //　蠍座             
  $aryPict['F8AF'] = array(0 => 'E64E',  1 => 200,          2 => '$Fg',       3 => 17);   //　射手座           
  $aryPict['F8B0'] = array(0 => 'E64F',  1 => 201,          2 => '$Fh',       3 => 18);   //　山羊座           
  $aryPict['F8B1'] = array(0 => 'E650',  1 => 202,          2 => '$Fi',       3 => 19);   //　水瓶座           
  $aryPict['F8B2'] = array(0 => 'E651',  1 => 203,          2 => '$Fj',       3 => 20);   //　魚座             
  $aryPict['F8B3'] = array(0 => 'E652',  1 => 218,          2 => '〓',          3 => 21);   //　スポーツ         
  $aryPict['F8B4'] = array(0 => 'E653',  1 => 45,           2 => '$G6',       3 => 22);   //　野球             
  $aryPict['F8B5'] = array(0 => 'E654',  1 => 306,          2 => '$G4',       3 => 23);   //　ゴルフ           
  $aryPict['F8B6'] = array(0 => 'E655',  1 => 220,          2 => '$G5',       3 => 24);   //　テニス           
  $aryPict['F8B7'] = array(0 => 'E656',  1 => 219,          2 => '$G8',       3 => 25);   //　サッカー         
  $aryPict['F8B8'] = array(0 => 'E657',  1 => 421,          2 => '$G3',       3 => 26);   //　スキー           
  $aryPict['F8B9'] = array(0 => 'E658',  1 => 307,          2 => '$PJ',       3 => 27);   //　バスケットボール 
  $aryPict['F8BA'] = array(0 => 'E659',  1 => 222,          2 => '$ER',       3 => 28);   //　モータースポーツ 
  $aryPict['F8BB'] = array(0 => 'E65A',  1 => 308,          2 => '〓',          3 => 29);   //　ポケットベル     
  $aryPict['F8BC'] = array(0 => 'E65B',  1 => 172,          2 => '$G>',       3 => 30);   //　電車             
  $aryPict['F8BD'] = array(0 => 'E65C',  1 => 341,          2 => '$PT',       3 => 31);   //　地下鉄           
  $aryPict['F8BE'] = array(0 => 'E65D',  1 => 217,          2 => '$PU',       3 => 32);   //　新幹線           
  $aryPict['F8BF'] = array(0 => 'E65E',  1 => 125,          2 => '$G;',       3 => 33);   //　車（セダン）     
  $aryPict['F8C0'] = array(0 => 'E65F',  1 => 125,          2 => '$PN',       3 => 34);   //　車（ＲＶ）       
  $aryPict['F8C1'] = array(0 => 'E660',  1 => 216,          2 => '$Ey',       3 => 35);   //　バス             
  $aryPict['F8C2'] = array(0 => 'E661',  1 => 379,          2 => '$F"',       3 => 36);   //　船               
  $aryPict['F8C3'] = array(0 => 'E662',  1 => 168,          2 => '$G=',       3 => 37);   //　飛行機           
  $aryPict['F8C4'] = array(0 => 'E663',  1 => 112,          2 => '$GV',       3 => 38);   //　家               
  $aryPict['F8C5'] = array(0 => 'E664',  1 => 156,          2 => '$GX',       3 => 39);   //　ビル             
  $aryPict['F8C6'] = array(0 => 'E665',  1 => 375,          2 => '$Es',       3 => 40);   //　郵便局           
  $aryPict['F8C7'] = array(0 => 'E666',  1 => 376,          2 => '$Eu',       3 => 41);   //　病院             
  $aryPict['F8C8'] = array(0 => 'E667',  1 => 212,          2 => '$Em',       3 => 42);   //　銀行             
  $aryPict['F8C9'] = array(0 => 'E668',  1 => 205,          2 => '$Et',       3 => 43);   //　ＡＴＭ           
  $aryPict['F8CA'] = array(0 => 'E669',  1 => 378,          2 => '$Ex',       3 => 44);   //　ホテル           
  $aryPict['F8CB'] = array(0 => 'E66A',  1 => 206,          2 => '$Ev',       3 => 45);   //　コンビニ         
  $aryPict['F8CC'] = array(0 => 'E66B',  1 => 213,          2 => '$GZ',       3 => 46);   //　ガソリンスタンド 
  $aryPict['F8CD'] = array(0 => 'E66C',  1 => 208,          2 => '$Eo',       3 => 47);   //　駐車場           
  $aryPict['F8CE'] = array(0 => 'E66D',  1 => 99,           2 => '$En',       3 => 48);   //　信号             
  $aryPict['F8CF'] = array(0 => 'E66E',  1 => 207,          2 => '$Eq',       3 => 49);   //　トイレ           
  $aryPict['F8D0'] = array(0 => 'E66F',  1 => 146,          2 => '$Gc',       3 => 50);   //　レストラン       
  $aryPict['F8D1'] = array(0 => 'E670',  1 => 93,           2 => '$Ge',       3 => 51);   //　喫茶店           
  $aryPict['F8D2'] = array(0 => 'E671',  1 => 52,           2 => '$Gd',       3 => 52);   //　バー             
  $aryPict['F8D3'] = array(0 => 'E672',  1 => 65,           2 => '$Gg',       3 => 53);   //　ビール           
  $aryPict['F8D4'] = array(0 => 'E673',  1 => 245,          2 => '$E@',       3 => 54);   //　ファーストフード 
  $aryPict['F8D5'] = array(0 => 'E674',  1 => 124,          2 => '$E^',       3 => 55);   //　ブティック       
  $aryPict['F8D6'] = array(0 => 'E675',  1 => 104,          2 => '$O3',       3 => 56);   //　美容院           
  $aryPict['F8D7'] = array(0 => 'E676',  1 => 289,          2 => '$G\',       3 => 57);   //　カラオケ         
  $aryPict['F8D8'] = array(0 => 'E677',  1 => 110,          2 => '$G]',       3 => 58);   //　映画             
  $aryPict['F8D9'] = array(0 => 'E678',  1 => 70,           2 => '$FV',       3 => 59);   //　右斜め上         
  $aryPict['F8DA'] = array(0 => 'E679',  1 => 223,          2 => '〓',          3 => 60);   //　遊園地           
  $aryPict['F8DB'] = array(0 => 'E67A',  1 => 294,          2 => '$O*',       3 => 61);   //　音楽             
  $aryPict['F8DC'] = array(0 => 'E67B',  1 => 309,          2 => '$Q"',       3 => 62);   //　アート           
  $aryPict['F8DD'] = array(0 => 'E67C',  1 => 494,          2 => '$Q#',       3 => 63);   //　演劇             
  $aryPict['F8DE'] = array(0 => 'E67D',  1 => 311,          2 => '〓',          3 => 64);   //　イベント         
  $aryPict['F8DF'] = array(0 => 'E67E',  1 => 106,          2 => '$EE',       3 => 65);   //　チケット         
  $aryPict['F8E0'] = array(0 => 'E67F',  1 => 176,          2 => '$O.',       3 => 66);   //　喫煙             
  $aryPict['F8E1'] = array(0 => 'E680',  1 => 177,          2 => '$F(',       3 => 67);   //　禁煙             
  $aryPict['F8E2'] = array(0 => 'E681',  1 => 94,           2 => '$G(',       3 => 68);   //　カメラ           
  $aryPict['F8E3'] = array(0 => 'E682',  1 => 83,           2 => '$OC',       3 => 69);   //　カバン           
  $aryPict['F8E4'] = array(0 => 'E683',  1 => 122,          2 => '$Eh',       3 => 70);   //　本               
  $aryPict['F8E5'] = array(0 => 'E684',  1 => 312,          2 => '$O4',       3 => 71);   //　リボン           
  $aryPict['F8E6'] = array(0 => 'E685',  1 => 144,          2 => '$E2',       3 => 72);   //　プレゼント       
  $aryPict['F8E7'] = array(0 => 'E686',  1 => 313,          2 => '$Ok',       3 => 73);   //　バースデー       
  $aryPict['F8E8'] = array(0 => 'E687',  1 => 85,           2 => '$G)',       3 => 74);   //　電話             
  $aryPict['F8E9'] = array(0 => 'E688',  1 => 161,          2 => '$G*',       3 => 75);   //　携帯電話         
  $aryPict['F8EA'] = array(0 => 'E689',  1 => 56,           2 => '$O!',       3 => 76);   //　メモ             
  $aryPict['F8EB'] = array(0 => 'E68A',  1 => 288,          2 => '$EJ',       3 => 77);   //　ＴＶ             
  $aryPict['F8EC'] = array(0 => 'E68B',  1 => 232,          2 => '[ｹﾞｰﾑ]',      3 => 78);   //　ゲーム           
  $aryPict['F8ED'] = array(0 => 'E68C',  1 => 300,          2 => '$EF',       3 => 79);   //　ＣＤ             
  $aryPict['F8EE'] = array(0 => 'E68D',  1 => 414,          2 => '$F,',       3 => 80);   //　ハート           
  $aryPict['F8EF'] = array(0 => 'E68E',  1 => 314,          2 => '$F.',       3 => 81);   //　スペード         
  $aryPict['F8F0'] = array(0 => 'E68F',  1 => 315,          2 => '$F-',       3 => 82);   //　ダイヤ           
  $aryPict['F8F1'] = array(0 => 'E690',  1 => 316,          2 => '$F/',       3 => 83);   //　クラブ           
  $aryPict['F8F2'] = array(0 => 'E691',  1 => 317,          2 => '$P9',       3 => 84);   //　目               
  $aryPict['F8F3'] = array(0 => 'E692',  1 => 318,          2 => '$P;',       3 => 85);   //　耳               
  $aryPict['F8F4'] = array(0 => 'E693',  1 => 817,          2 => '$G0',       3 => 86);   //　手（グー）       
  $aryPict['F8F5'] = array(0 => 'E694',  1 => 319,          2 => '$G1',       3 => 87);   //　手（チョキ）     
  $aryPict['F8F6'] = array(0 => 'E695',  1 => 320,          2 => '$G2',       3 => 88);   //　手（パー）       
  $aryPict['F8F7'] = array(0 => 'E696',  1 => 43,           2 => '$FX',       3 => 89);   //　右斜め下         
  $aryPict['F8F8'] = array(0 => 'E697',  1 => 42,           2 => '$FW',       3 => 90);   //　左斜め上         
  $aryPict['F8F9'] = array(0 => 'E698',  1 => 728,          2 => '$QV',       3 => 91);   //　足               
  $aryPict['F8FA'] = array(0 => 'E699',  1 => 729,          2 => '$G\'',      3 => 92);   //　くつ             
  $aryPict['F8FB'] = array(0 => 'E69A',  1 => 116,          2 => '[ﾒｶﾞﾈ]',      3 => 93);   //　眼鏡             
  $aryPict['F8FC'] = array(0 => 'E69B',  1 => 178,          2 => '$F*',       3 => 94);   //　車椅子           
  $aryPict['F940'] = array(0 => 'E69C',  1 => 321,          2 => '●',          3 => 95);   //　新月             
  $aryPict['F941'] = array(0 => 'E69D',  1 => 322,          2 => '$Gl',       3 => 96);   //　やや欠け月       
  $aryPict['F942'] = array(0 => 'E69E',  1 => 323,          2 => '$Gl',       3 => 97);   //　半月             
  $aryPict['F943'] = array(0 => 'E69F',  1 => 15,           2 => '$Gl',       3 => 98);   //　三日月           
  $aryPict['F944'] = array(0 => 'E6A0',  1 => 47,           2 => '○',          3 => 99);   //　満月             
  $aryPict['F945'] = array(0 => 'E6A1',  1 => 134,          2 => '$Gr',       3 => 100);  //　犬               
  $aryPict['F946'] = array(0 => 'E6A2',  1 => 251,          2 => '$Go',       3 => 101);  //　猫               
  $aryPict['F947'] = array(0 => 'E6A3',  1 => 169,          2 => '$G<',       3 => 102);  //　リゾート         
  $aryPict['F948'] = array(0 => 'E6A4',  1 => 234,          2 => '$GS',       3 => 103);  //　クリスマス       
  $aryPict['F949'] = array(0 => 'E6A5',  1 => 71,           2 => '$FY',       3 => 104);  //　左斜め下         

  $aryPict['F950'] = array(0 => 'E6AC',  1 => 226,          2 => '$OD',       3 => 167);  //　カチンコ         
  $aryPict['F951'] = array(0 => 'E6AD',  1 => 233,          2 => '[ふくろ]',    3 => 168);  //　ふくろ           
  $aryPict['F952'] = array(0 => 'E6AE',  1 => 508,          2 => '[ﾍﾟﾝ]',       3 => 169);  //　ペン             
  $aryPict['F955'] = array(0 => 'E6B1',  1 => 80,           2 => '〓',          3 => 170);  //　人影             
  $aryPict['F956'] = array(0 => 'E6B2',  1 => '[ｲｽ]',       2 => '$E?',       3 => 171);  //　いす             
  $aryPict['F957'] = array(0 => 'E6B3',  1 => 490,          2 => '$Pk',       3 => 172);  //　夜               
  $aryPict['F95B'] = array(0 => 'E6B7',  1 => 63,           2 => '[SOON]',      3 => 173);  //　soon             
  $aryPict['F95C'] = array(0 => 'E6B8',  1 => 808,          2 => '[ON]',        3 => 174);  //　on               
  $aryPict['F95D'] = array(0 => 'E6B9',  1 => 64,           2 => '[END]',       3 => 175);  //　end              
  $aryPict['F95E'] = array(0 => 'E6BA',  1 => 46,           2 => '$GM',       3 => 176);  //　時計             

  $aryPict['F972'] = array(0 => 'E6CE',  1 => 513,          2 => '$E$',       3 => 105);  //　phone to         
  $aryPict['F973'] = array(0 => 'E6CF',  1 => 784,          2 => '$E#',       3 => 106);  //　mail to          
  $aryPict['F974'] = array(0 => 'E6D0',  1 => 166,          2 => '$G+',       3 => 107);  //　fax to           
  $aryPict['F975'] = array(0 => 'E6D1',  1 => '[iﾓｰﾄﾞ]',    2 => '[iﾓｰﾄﾞ]',     3 => 108);  //　iモード          
  $aryPict['F976'] = array(0 => 'E6D2',  1 => '[iﾓｰﾄﾞ]',    2 => '[iﾓｰﾄﾞ]',     3 => 109);  //　iモード（枠付き）
  $aryPict['F977'] = array(0 => 'E6D3',  1 => 108,          2 => '$E#',       3 => 110);  //　メール           
  $aryPict['F978'] = array(0 => 'E6D4',  1 => '[ﾄﾞｺﾓ]',     2 => '[ﾄﾞｺﾓ]',      3 => 111);  //　ドコモ提供       
  $aryPict['F979'] = array(0 => 'E6D5',  1 => '[ﾄﾞｺﾓﾎﾟｲﾝﾄ]',2 => '[ﾄﾞｺﾓﾎﾟｲﾝﾄ]', 3 => 112);  //　ドコモポイント   
  $aryPict['F97A'] = array(0 => 'E6D6',  1 => 109,          2 => '[\]',         3 => 113);  //　有料             
  $aryPict['F97B'] = array(0 => 'E6D7',  1 => 299,          2 => '[FREE]',      3 => 114);  //　無料             
  $aryPict['F97C'] = array(0 => 'E6D8',  1 => 385,          2 => '$FI',       3 => 115);  //　ID               
  $aryPict['F97D'] = array(0 => 'E6D9',  1 => 120,          2 => '$G_',       3 => 116);  //　パスワード       
  $aryPict['F97E'] = array(0 => 'E6DA',  1 => 118,          2 => '〓',          3 => 117);  //　次項有           
  $aryPict['F980'] = array(0 => 'E6DB',  1 => 324,          2 => '[CL]' ,       3 => 118);  //　クリア           
  $aryPict['F981'] = array(0 => 'E6DC',  1 => 119,          2 => '$E4',       3 => 119);  //　サーチ（調べる） 
  $aryPict['F982'] = array(0 => 'E6DD',  1 => 334,          2 => '$F2',       3 => 120);  //　ＮＥＷ           
  $aryPict['F983'] = array(0 => 'E6DE',  1 => 730,          2 => '〓',          3 => 121);  //　位置情報         
  $aryPict['F984'] = array(0 => 'E6DF',  1 => '[ﾌﾘｰﾀﾞｲﾔﾙ]', 2 => '$F1',       3 => 122);  //　フリーダイヤル   
  $aryPict['F985'] = array(0 => 'E6E0',  1 => 818,          2 => '$F0',       3 => 123);  //　シャープダイヤル 
  $aryPict['F986'] = array(0 => 'E6E1',  1 => 4,            2 => '[Q]',         3 => 124);  //　モバＱ           
  $aryPict['F987'] = array(0 => 'E6E2',  1 => 180,          2 => '$F<',       3 => 125);  //　1                
  $aryPict['F988'] = array(0 => 'E6E3',  1 => 181,          2 => '$F=',       3 => 126);  //　2                
  $aryPict['F989'] = array(0 => 'E6E4',  1 => 182,          2 => '$F>',       3 => 127);  //　3                
  $aryPict['F98A'] = array(0 => 'E6E5',  1 => 183,          2 => '$F?',       3 => 128);  //　4                
  $aryPict['F98B'] = array(0 => 'E6E6',  1 => 184,          2 => '$F@',       3 => 129);  //　5                
  $aryPict['F98C'] = array(0 => 'E6E7',  1 => 185,          2 => '$FA',       3 => 130);  //　6                
  $aryPict['F98D'] = array(0 => 'E6E8',  1 => 186,          2 => '$FB',       3 => 131);  //　7                
  $aryPict['F98E'] = array(0 => 'E6E9',  1 => 187,          2 => '$FC',       3 => 132);  //　8                
  $aryPict['F98F'] = array(0 => 'E6EA',  1 => 188,          2 => '$FD',       3 => 133);  //　9                
  $aryPict['F990'] = array(0 => 'E6EB',  1 => 325,          2 => '$FE',       3 => 134);  //　0                
  $aryPict['F991'] = array(0 => 'E6EC',  1 => 51,           2 => '$GB',       3 => 136);  //　黒ハート         
  $aryPict['F992'] = array(0 => 'E6ED',  1 => 328,          2 => '$OH',       3 => 137);  //　揺れるハート     
  $aryPict['F993'] = array(0 => 'E6EE',  1 => 265,          2 => '$GC',       3 => 138);  //　失恋             
  $aryPict['F994'] = array(0 => 'E6EF',  1 => 266,          2 => '$OG',       3 => 139);  //　ハートたち       
  $aryPict['F995'] = array(0 => 'E6F0',  1 => 257,          2 => '$Gw',       3 => 140);  //　わーい           
  $aryPict['F996'] = array(0 => 'E6F1',  1 => 258,          2 => '$Gy',       3 => 141);  //　ちっ             
  $aryPict['F997'] = array(0 => 'E6F2',  1 => 441,          2 => '$Gx',       3 => 142);  //　がく～           
  $aryPict['F998'] = array(0 => 'E6F3',  1 => 444,          2 => '$P\'',        3 => 143);  //　もうやだ～       
  $aryPict['F999'] = array(0 => 'E6F4',  1 => 327,          2 => '$P&',       3 => 144);  //　ふらふら         
  $aryPict['F99A'] = array(0 => 'E6F5',  1 => 731,          2 => '$FV',       3 => 145);  //　グッド           
  $aryPict['F99B'] = array(0 => 'E6F6',  1 => 343,          2 => '$G^',       3 => 146);  //　るんるん         
  $aryPict['F99C'] = array(0 => 'E6F7',  1 => 224,          2 => '$EC',       3 => 147);  //　いい気分         
  $aryPict['F99D'] = array(0 => 'E6F8',  1 => 19,           2 => '〓',          3 => 148);  //　かわいい         
  $aryPict['F99E'] = array(0 => 'E6F9',  1 => 273,          2 => '$G#',       3 => 149);  //　キスマーク       
  $aryPict['F99F'] = array(0 => 'E6FA',  1 => 420,          2 => '$ON',       3 => 150);  //　ぴかぴか         
  $aryPict['F9A0'] = array(0 => 'E6FB',  1 => 77,           2 => '$E/',       3 => 151);  //　ひらめき         
  $aryPict['F9A1'] = array(0 => 'E6FC',  1 => 262,          2 => '$OT',       3 => 152);  //　むかっ           
  $aryPict['F9A2'] = array(0 => 'E6FD',  1 => 281,          2 => '$G-',       3 => 153);  //　パンチ           
  $aryPict['F9A3'] = array(0 => 'E6FE',  1 => 268,          2 => '$O1',       3 => 154);  //　爆弾             
  $aryPict['F9A4'] = array(0 => 'E6FF',  1 => 291,          2 => '$OF',       3 => 155);  //　ムード           
  $aryPict['F9A5'] = array(0 => 'E700',  1 => 732,          2 => '$FX',       3 => 156);  //　バッド           
  $aryPict['F9A6'] = array(0 => 'E701',  1 => 261,          2 => '$E\',       3 => 157);  //　眠い(睡眠)       
  $aryPict['F9A7'] = array(0 => 'E702',  1 => 2,            2 => '$GA',       3 => 158);  //　！               
  $aryPict['F9A8'] = array(0 => 'E703',  1 => 733,          2 => '!?',          3 => 159);  //　！？             
  $aryPict['F9A9'] = array(0 => 'E704',  1 => 734,          2 => '!!',          3 => 160);  //　！！             
  $aryPict['F9AA'] = array(0 => 'E705',  1 => 329,          2 => '〓',          3 => 161);  //　どんっ（衝撃）   
  $aryPict['F9AB'] = array(0 => 'E706',  1 => 330,          2 => '$OQ',       3 => 162);  //　あせあせ         
  $aryPict['F9AC'] = array(0 => 'E707',  1 => 263,          2 => '$OQ',       3 => 163);  //　たらーっ         
  $aryPict['F9AD'] = array(0 => 'E708',  1 => 282,          2 => '$OP',       3 => 164);  //　ダッシュ         
  $aryPict['F9AE'] = array(0 => 'E709',  1 => 810,          2 => '〓',          3 => 165);  //　ー（長音記号１） 
  $aryPict['F9AF'] = array(0 => 'E70A',  1 => 735,          2 => '〓',          3 => 166);  //　ー（長音記号２） 

  $aryPict['F9B0'] = array(0 => 'E70B',  1 => 326,          2 => '$Fm',       3 => 135);  //　決定             

  /* 拡張絵文字 (iモード対応HTML4.0対応機種以降) */
  $aryPict['F9B1'] = array(0 => 'E70C',  1 => '[iｱﾌﾟﾘ]',    2 => '[iｱﾌﾟﾘ]',     3 => 177);  //　iアプリ          
  $aryPict['F9B2'] = array(0 => 'E70D',  1 => '[iｱﾌﾟﾘ]',    2 => '[iｱﾌﾟﾘ]',     3 => 178);  //　iアプリ（枠付き）
  $aryPict['F9B3'] = array(0 => 'E70E',  1 => 335,          2 => '$G&',       3 => 179);  //　Tシャツ          
  $aryPict['F9B4'] = array(0 => 'E70F',  1 => 290,          2 => '[財布]',      3 => 180);  //　がま口財布       
  $aryPict['F9B5'] = array(0 => 'E710',  1 => 295,          2 => '$O<',       3 => 181);  //　化粧             
  $aryPict['F9B6'] = array(0 => 'E711',  1 => 805,          2 => '[ｼﾞｰﾝｽﾞ]',    3 => 182);  //　ジーンズ         
  $aryPict['F9B7'] = array(0 => 'E712',  1 => 221,          2 => '[ｽﾉﾎﾞ]',      3 => 183);  //　スノボ           
  $aryPict['F9B8'] = array(0 => 'E713',  1 => 48,           2 => '$OE',       3 => 184);  //　チャペル         
  $aryPict['F9B9'] = array(0 => 'E714',  1 => '[ﾄﾞｱ]',      2 => '[ﾄﾞｱ]',       3 => 185);  //　ドア             
  $aryPict['F9BA'] = array(0 => 'E715',  1 => 233,          2 => '$EO',       3 => 186);  //　ドル袋           
  $aryPict['F9BB'] = array(0 => 'E716',  1 => 337,          2 => '$G,',       3 => 187);  //　パソコン         
  $aryPict['F9BC'] = array(0 => 'E717',  1 => 806,          2 => '$E#',       3 => 188);  //　ラブレター       
  $aryPict['F9BD'] = array(0 => 'E718',  1 => 152,          2 => '[ﾚﾝﾁ]',       3 => 189);  //　レンチ           
  $aryPict['F9BE'] = array(0 => 'E719',  1 => 149,          2 => '$O!',       3 => 190);  //　鉛筆             
  $aryPict['F9BF'] = array(0 => 'E71A',  1 => 354,          2 => '$E.',       3 => 191);  //　王冠             
  $aryPict['F9C0'] = array(0 => 'E71B',  1 => 72,           2 => '$GT',       3 => 192);  //　指輪             
  $aryPict['F9C1'] = array(0 => 'E71C',  1 => 58,           2 => '[砂時計]',    3 => 193);  //　砂時計           
  $aryPict['F9C2'] = array(0 => 'E71D',  1 => 215,          2 => '$EV',       3 => 194);  //　自転車           
  $aryPict['F9C3'] = array(0 => 'E71E',  1 => 423,          2 => '$OX',       3 => 195);  //　湯のみ           
  $aryPict['F9C4'] = array(0 => 'E71F',  1 => 25,           2 => '[腕時計]',    3 => 196);  //　腕時計           
  $aryPict['F9C5'] = array(0 => 'E720',  1 => 441,          2 => '$P#',       3 => 197);  //　考えてる顔       
  $aryPict['F9C6'] = array(0 => 'E721',  1 => 446,          2 => '$P*',       3 => 198);  //　ほっとした顔     
  $aryPict['F9C7'] = array(0 => 'E722',  1 => 257,          2 => '$OQ',       3 => 199);  //　冷や汗           
  $aryPict['F9C8'] = array(0 => 'E723',  1 => 351,          2 => '$E(',       3 => 200);  //　冷や汗2          
  $aryPict['F9C9'] = array(0 => 'E724',  1 => 779,          2 => '$P6',       3 => 201);  //　ぷっくっくな顔   
  $aryPict['F9CA'] = array(0 => 'E725',  1 => 450,          2 => '$P.',       3 => 202);  //　ボケーっとした顔 
  $aryPict['F9CB'] = array(0 => 'E726',  1 => 349,          2 => '$E&',       3 => 203);  //　目がハート       
  $aryPict['F9CC'] = array(0 => 'E727',  1 => 287,          2 => '$G.',       3 => 204);  //　指でOK           
  $aryPict['F9CD'] = array(0 => 'E728',  1 => 264,          2 => '$E%',       3 => 205);  //　あっかんべー     
  $aryPict['F9CE'] = array(0 => 'E729',  1 => 348,          2 => '$P%',       3 => 206);  //　ウィンク         
  $aryPict['F9CF'] = array(0 => 'E72A',  1 => 446,          2 => '$P*',       3 => 207);  //　うれしい顔       
  $aryPict['F9D0'] = array(0 => 'E72B',  1 => 443,          2 => '$P&',       3 => 208);  //　がまん顔         
  $aryPict['F9D1'] = array(0 => 'E72C',  1 => 440,          2 => '$P"',       3 => 209);  //　猫2              
  $aryPict['F9D2'] = array(0 => 'E72D',  1 => 259,          2 => '$P1',       3 => 210);  //　泣き顔           
  $aryPict['F9D3'] = array(0 => 'E72E',  1 => 791,          2 => '$P3',       3 => 211);  //　涙               
  $aryPict['F9D4'] = array(0 => 'E72F',  1 => 464,          2 => '[NG]',        3 => 212);  //　NG               
  $aryPict['F9D5'] = array(0 => 'E730',  1 => 143,          2 => '[ｸﾘｯﾌﾟ]',     3 => 213);  //　クリップ         
  $aryPict['F9D6'] = array(0 => 'E731',  1 => 81,           2 => '$Fn',       3 => 214);  //　コピーライト     
  $aryPict['F9D7'] = array(0 => 'E732',  1 => 54,           2 => '$QW',       3 => 215);  //　トレードマーク   
  $aryPict['F9D8'] = array(0 => 'E733',  1 => 218,          2 => '$E5',       3 => 216);  //　走る人           
  $aryPict['F9D9'] = array(0 => 'E734',  1 => 279,          2 => '$O5',       3 => 217);  //　マル秘           
  $aryPict['F9DA'] = array(0 => 'E735',  1 => 807,          2 => '〓',          3 => 218);  //　リサイクル       
  $aryPict['F9DB'] = array(0 => 'E736',  1 => 82,           2 => '$Fo',       3 => 219);  //　トレードマーク   
  $aryPict['F9DC'] = array(0 => 'E737',  1 => 1,            2 => '$Fr',       3 => 220);  //　危険・警告       
  $aryPict['F9DD'] = array(0 => 'E738',  1 => 31,           2 => '[禁]' ,       3 => 221);  //　禁止             
  $aryPict['F9DE'] = array(0 => 'E739',  1 => 387,          2 => '$FK',       3 => 222);  //　空室・空席・空車 
  $aryPict['F9DF'] = array(0 => 'E73A',  1 => '[合]',       2 => '[合]' ,       3 => 223);  //　合格マーク       
  $aryPict['F9E0'] = array(0 => 'E73B',  1 => 386,          2 => '$FJ',       3 => 224);  //　満室・満席・満車 
  $aryPict['F9E1'] = array(0 => 'E73C',  1 => 808,          2 => '⇔',          3 => 225);  //　矢印左右         
  $aryPict['F9E2'] = array(0 => 'E73D',  1 => 809,          2 => '↑↓',        3 => 226);  //　矢印上下         
  $aryPict['F9E3'] = array(0 => 'E73E',  1 => 377,          2 => '$Ew',       3 => 227);  //　学校             
  $aryPict['F9E4'] = array(0 => 'E73F',  1 => 810,          2 => '$P^',       3 => 228);  //　波               
  $aryPict['F9E5'] = array(0 => 'E740',  1 => 342,          2 => '$G[',       3 => 229);  //　富士山           
  $aryPict['F9E6'] = array(0 => 'E741',  1 => 53,           2 => '$E0',       3 => 230);  //　クローバー       
  $aryPict['F9E7'] = array(0 => 'E742',  1 => 241,          2 => '[ﾁｪﾘｰ]',      3 => 231);  //　さくらんぼ       
  $aryPict['F9E8'] = array(0 => 'E743',  1 => 113,          2 => '$O$',       3 => 232);  //　チューリップ     
  $aryPict['F9E9'] = array(0 => 'E744',  1 => 739,          2 => '[ﾊﾞﾅﾅ]',      3 => 233);  //　バナナ           
  $aryPict['F9EA'] = array(0 => 'E745',  1 => 434,          2 => '$Oe',       3 => 234);  //　りんご           
  $aryPict['F9EB'] = array(0 => 'E746',  1 => 811,          2 => '$E0',       3 => 235);  //　芽               
  $aryPict['F9EC'] = array(0 => 'E747',  1 => 133,          2 => '$E8',       3 => 236);  //　もみじ           
  $aryPict['F9ED'] = array(0 => 'E748',  1 => 235,          2 => '$GP',       3 => 237);  //　桜               
  $aryPict['F9EE'] = array(0 => 'E749',  1 => 244,          2 => '$Ob',       3 => 238);  //　おにぎり         
  $aryPict['F9EF'] = array(0 => 'E74A',  1 => 239,          2 => '$Gf',       3 => 239);  //　ショートケーキ   
  $aryPict['F9F0'] = array(0 => 'E74B',  1 => 400,          2 => '$O+',       3 => 240);  //　とっくり         
  $aryPict['F9F1'] = array(0 => 'E74C',  1 => 333,          2 => '$O`',       3 => 241);  //　どんぶり         
  $aryPict['F9F2'] = array(0 => 'E74D',  1 => 424,          2 => '$OY',       3 => 242);  //　パン             
  $aryPict['F9F3'] = array(0 => 'E74E',  1 => 812,          2 => '[ｶﾀﾂﾑﾘ]',     3 => 243);  //　かたつむり       
  $aryPict['F9F4'] = array(0 => 'E74F',  1 => 78,           2 => '$QC',       3 => 244);  //　ひよこ           
  $aryPict['F9F5'] = array(0 => 'E750',  1 => 252,          2 => '$Gu',       3 => 245);  //　ペンギン         
  $aryPict['F9F6'] = array(0 => 'E751',  1 => 203,          2 => '$G9',       3 => 246);  //　魚               
  $aryPict['F9F7'] = array(0 => 'E752',  1 => 454,          2 => '$Gv',       3 => 247);  //　うまい！         
  $aryPict['F9F8'] = array(0 => 'E753',  1 => 814,          2 => '$P$',       3 => 248);  //　ウッシッシ       
  $aryPict['F9F9'] = array(0 => 'E754',  1 => 248,          2 => '$G:',       3 => 249);  //　ウマ             
  $aryPict['F9FA'] = array(0 => 'E755',  1 => 254,          2 => '$E+',       3 => 250);  //　ブタ             
  $aryPict['F9FB'] = array(0 => 'E756',  1 => 12,           2 => '$Gd',       3 => 251);  //　ワイングラス     
  $aryPict['F9FC'] = array(0 => 'E757',  1 => 350,          2 => '$E\'',      3 => 252);  //　げっそり         
return $aryPict;
}
}
?>