<?php

ini_set('log_errors','on'); //ログを取るか
ini_set('error_log','php.log');
session_start();

$demons = array();
// モンスター識別クラス
class Individual{
  const HANTENG = 1;
  const GYOKKO = 2;
  const KOKUSHIBOU = 3;
  const DOMA = 4;
  const AKAZA = 5;
  const KAIGAKU = 6;
}
// 抽象クラス（生き物クラス）
abstract class Creature{
  protected $name;
  protected $hp;
  protected $attackMin;
  protected $attackMax;
  abstract public function sayCry();
  public function setName($str){
    $this->name = $str;
  }
  public function getName(){
    return $this->name;
  }
  public function setHp($num){
    $this->hp = $num;
  }
  public function getHp(){
    return $this->hp;
  }
  public function attack($targetObj){
    $attackPoint = mt_rand($this->attackMin, $this->attackMax);
    if(!mt_rand(0,9)){
      $attackPoint = $attackPoint * 1.5;
      $attackPoint = (int)$attackPoint;
      History::set($this->getName().'のクリティカルヒット!!');
    }
    $targetObj->setHp($targetObj->getHp()-$attackPoint);
    History::set($attackPoint.'ポイントのダメージ！');
  }
}
// 主人公クラス
class Human extends Creature{
  public function __construct($name, $hp, $attackMin, $attackMax){
    $this->name = $name;
    $this->hp = $hp;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  public function sayCry(){
    History::set($this->name.'が叫ぶ！');
    if(mt_rand(0,9)){
      History::set('頑張れ俺 頑張れーー！！');
    }else{
      History::set('燃やせ 燃やせ 燃やせ！ 心を燃やせ！！');
    }
  }
}
class Demons extends Creature{
  protected $img;
  protected $individual;
  public function __construct($name, $individual, $hp, $img, $attackMin, $attackMax){
    $this->name = $name;
    $this->individual = $individual;
    $this->hp = $hp;
    $this->img = $img;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  public function getImg(){
    return $this->img;
  }
  public function sayCry(){
    History::set($this->name.'が叫ぶ！');
    switch($this->individual){
      case Individual::HANTENG :
        History::set('弱き者をいたぶる鬼畜 不快 不愉快 極まれり 極悪人共めが');
        break;
      case Individual::GYOKKO :
        History::set('あれは首を生けるものではない…だがそれもまたいい');
        break;
      case Individual::KOKUSHIBOU :
        History::set('さらなる高みへの…開けた道をも…自ら放棄するとは…軟弱千万');
        break;
      case Individual::DOMA :
        History::set('誰もが皆死ぬのを怖がるから だから俺が”食べてあげてる” 俺と共に生きていくんだ永遠の時を');
        break;
      case Individual::AKAZA :
        History::set('そう 弱者には虫唾が走る反吐が出る 淘汰されるのは自然の摂理に他ならない');
        break;
      case Individual::KAIGAKU :
        History::set('俺は常に！！どんな時も！！正しく俺を評価する者につく');
        break;
    }
  }
}
interface HistoryInterface{
  public static function set($str);
  public static function clear();
}
// 履歴管理クラス
class History implements HistoryInterface{
  public static function set($str){
    // セッションhistoryが作られてなければ作る
    if(empty($_SESSION['history'])) $_SESSION['history'] = '';
    // 文字列をセッションhistoryへ格納
    $_SESSION['history'] .= $str.'<br>';
  }
  public static function clear(){
    unset($_SESSION['history']);
  }
}
// インスタンス生成
$human = new Human('炭治郎',600,60,120);
$demons[] = new Demons('半天狗', Individual::HANTENG, 100, '/assets/images/use/oni_01.jpg', 20, 40);
// $demons[] = new Demons('玉壺',Individual::GYOKKO, 130,'/assets/images/use/oni_02.jpg',20,40);
// $demons[] = new Demons('黒死牟',Individual::KOKUSHIBOU, 300,'/assets/images/use/oni_03.jpg',20,40);
// $demons[] = new Demons('童磨',Individual::DOMA, 200,'/assets/images/use/oni_04.jpg',20,40);
// $demons[] = new Demons('猗窩座',Individual::AKAZA, 200,'/assets/images/use/oni_05.jpg',20,40);
// $demons[] = new Demons('獪岳',Individual::KAIGAKU, 80,'/assets/images/use/oni_06.jpg',10,30);

function createDemon(){
  global $demons;
  $demons = $demons[mt_rand(0,7)];
  History::set($demons->getName().'が現れた！');
  $_SESSION['demons'] = $demons;
}
function createHuman(){
  global $human;
  $_SESSION['human'] =  $human;
}
function init(){
  History::clear();
  History::set('初期化します！');
  $_SESSION['knockDownCount'] = 0;
  createHuman();
  createDemon();
}
function gameOver(){
  $_SESSION = array();
}

// 1.post送信されていた場合
if(!empty($_POST)){
  $attackFlgWoter = (!empty($_POST['water'])) ? true : false;
  $attackFlgSun = (!empty($_POST['sun'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false ;
  error_log('POSTされた！');

  if($startFlg){
    error_log('ゲームスタート！');
    History::set('ゲームスタート！');
    init();
  }else{
    // 攻撃するを押した場合
    if($attackFlgWoter || $attackFlgSun){

      // 鬼に攻撃を与える
      History::set($_SESSION['human']->getName().'の攻撃！');
      error_log($_SESSION['human']->getName());
      $_SESSION['human']->attack($_SESSION['demons']);
      $_SESSION['demons']->sayCry();

      // モンスターが攻撃をする
      History::set($_SESSION['demons']->getName().'の攻撃');
      $_SESSION['demons']->attack($_SESSION['human']);
      $_SESSION['human']->sayCry();

      // 自分のhpが0以下になったらゲームオーバー
      if($_SESSION['human']->getHp() <= 0){
        gameOver();
      }else{
        // hpが0以下になったら、別の鬼を出現させる
        if($_SESSION['demons']->getHp() <= 0){
          History::set($_SESSION['demons']->getName().'を倒した！');
          createDemon();
          $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
        }
      }
    }else{
      // 逃げるを押した場合
      History::set('逃げた！');
      createDemon();
    }
  }
  $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <!-- font -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@200;300;400;500;600;700;900&display=swap" rel="stylesheet">
  <link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet">

  <!-- css -->
  <link rel="stylesheet" href="/assets/css/style.css">

  <title>オブジェクト指向部</title>

</head>
<body>
  <header>
    <h1 class="main_heading">
      鬼滅の刃ゲーム
    </h1>
  </header>
  <main>
     <div class="l-content">
     <?php if(empty($_SESSION)){ ?>
        <h2 class="start_heading">ゲームスタート</h2>
          <form action="" method="POST">
            <input type="submit" name="start" value="▶ゲームスタート" class="start_btn">
          </form>
        <?php }else{ ?>
          <div class="demon_area">
          <?php       error_log($_SESSION['demons']->getImg());?>
          <div class="c-img"><img src="<?php echo $_SESSION['demons']->getImg();?>" alt=""><p class="hit_point">HP：<?php $_SESSION['demons']->getHp();?></p></div>
          <div class="log">
            <div class="history js-scroll-area">
              <?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : '';?>
            </div>
          </div>
        </div>
        <div class="hero_area">
          <div class="c-img"><img src="/assets/images/use/player.jpg" alt=""><p class="hit_point">HP：<?php $_SESSION['human']->getHp();?></p></div>
          <form class="command_area" method="POST" action="">
            <input type="submit" name="water" value="水の呼吸" class="water c-command">
            <input type="submit" name="sun" value="日の呼吸" class="sun c-command">
            <input type="submit" name="escape" value="逃げる" class="escape c-command">
          </form>
        </div>
       <div class="getItem">
        <p class="getItem_text"><i class="fas fa-syringe"></i>手に入れた血:<?php echo $_SESSION['knockDownCount']?>コ</p>
      </div>
      <?php } ?>
     </div>
  </main>
  <footer>
      <small>鬼滅の刃ゲーム</small>
  </footer>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script>
    $(function () {
      // フッター固定
      var $ftr = $('footer');
      if(window.innerHeight > $ftr.offset().top + $ftr.outerHeight() ){
        $ftr.attr({'style':'position:fixed; top:' + (window.innerHeight - $ftr.outerHeight()) + 'px;'});
      }
      // スクロールエリア
      var $scrollAuto = $('.js-scroll-area');
      $scrollAuto.animate({scrollTop: $scrollAuto[0].scrollHeight},'slow');
    });
  </script>
</body>
</html>