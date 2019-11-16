<?php
ini_set('log_errors','on');
ini_set('error_log','php.log');

// セッション
session_name("mario_game");
session_start();

//デバッグ
$debug_flg = false;
function debug($str){
    global $debug_flg;
    if(!empty($debug_flg)){
     error_log('デバッグ:'.$str);
    }
}

function debugLogStart(){
    debug('>>>>>>>>>>>>>>>>>>>>>>>>>デバッグログ開始');
    debug('セッションID:'.session_id());
    debug('セッション変数の中身:'.print_r($_SESSION,true));
}
debugLogStart();

//モンスター格納配列
$monsters = array();


// キャラクタークラス
class Character{
    const MARIO = 1;
    const LUIGI = 2;
    const PEACH =3;
}

// 抽象クラス(生き物クラス)
abstract class Creature{
    protected $name;
    protected $hp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();
    
    //セッター・ゲッター
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
            $attackPoint = $attackPoint *1.5;
            $attackPoint = (int)$attackPoint;
            History::set($this->getName().'のクリティカルヒット!');
        }
        $targetObj->setHp($targetObj->getHp()-$attackPoint);
        History::set($attackPoint.'ポイントのダメージ!');
    }
}
//プレイヤークラス
class Player extends Creature{
    protected $character;
    public function __construct($name,$character,$hp,$attackMin,$attackMax) {
        $this->name = $name;
        $this->character = $character;
        $this->hp = $hp;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    public function setCharacter($num){
        $this->character = $num;
    }
    public function getCharacter(){
        return $this->character;
    }
public function sayCry(){
    switch($this->character){
        case Character::MARIO :
            History::set('ヤッフー!!!');
            break;
        case Character::LUIGI :
            History::set('マンマミーヤ');
            break;
        case Character::PEACH :
            History::set('レッツゴー!');
            break;
    }
}
}
//モンスタークラス
class Monster extends Creature{
    //プロパティ
    protected $img;
    
//コンストラクタ
public function __construct($name,$hp,$img,$attackMin,$attackMax) {
    $this->name = $name;
    $this->hp = $hp;
    $this->img = $img;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
}
    
//ゲッター
public function getImg(){
    return $this->img;
}
    public function sayCry(){
        History::set($this->name.'が叫ぶ!');
        History::set('ぐはぁっ!');
    }
}
//特殊攻撃を使えるモンスター
class SpecialMonster extends Monster{
    private $specialAttack;
    
    function __construct($name,$hp,$img,$attackMin,$attackMax,$specialAttack) {
        parent::__construct($name,$hp,$img,$attackMin,$attackMax);
        $this->specialAttack = $specialAttack;
    }
    //メソッド
    public function getSpecialAttack(){
        return $this->specialAttack;
    }
    //attackメソッドをオーバーライド
    public function attack($targetObj){
        if(!mt_rand(0,4)){//5分の1の確率で特殊攻撃
        History::set($this->name.'の特殊攻撃!!');
       $targetObj->setHp($targetObj->getHp() - $this->specialAttack );
        History::set($this->specialAttack.'ポイントのダメージを受けた!');
        }else{
        //通常攻撃の場合、親クラスの攻撃メソッドを呼び出す
        parent::attack($targetObj);
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
        $_SESSION['history'] .= $str.'<br>';
    }
    public static function clear(){
        unset($_SESSION['history']);
    }
}


//インスタンス生成
$player = new Player('マリオ',Character::MARIO,500,50,150);
$monsters[] = new Monster( 'クリボー',100,'img/monster01.jpeg',20,40 );
$monsters[] = new Monster( 'ノコノコ',300,'img/monster02.jpeg',20,60 );
$monsters[] = new Monster('ヘイホー',200,'img/monster03.jpeg',30,50 );
$monsters[] = new Monster('パタクリボー',400,'img/monster05.jpeg',50,80 );
$monsters[] = new Monster('パックランフラワー',150,'img/monster04.jpeg',30,60 );
$monsters[] = new Monster('ゲッソー',100,'img/monster07.jpeg',10,30 );
$monsters[] = new SpecialMonster('クッパJr',120,'img/monster06.jpeg',60,100,mt_rand(50,150) );
$monsters[] = new SpecialMonster('クッパ',180,'img/monster08.jpeg',100,200,mt_rand(60,200) );

function createMonster(){
    debug('モンスターが生成されました。');
    global $monsters;
    $monster = $monsters[mt_rand(0,7)];
    History::set($monster->getName().'が現れた！');
    $_SESSION['monster'] = $monster;
    debug('モンスター:'.print_r($_SESSION['monster'],true));
}
function createPlayer(){
    global $player;
    $_SESSION['player'] = $player;
}

function init(){
    History::clear();
    History::set('初期化します!');
    debug('初期化されました。');
    $_SESSION['knockDownCount'] = 0;
    createMonster();
    createPlayer();
}
function gameOver(){
    unset($_SESSION['history']);
    unset($_SESSION['knockDownCount']);
}

//1.post送信がある場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true : false;
    $startFlg = (!empty($_POST['start'])) ? true :false;
    debug('POST送信があります。');

if($startFlg){
    debug('ゲームがスタートされました。');
    History::set('ゲームスタート!');
    init();
}else{
    //攻撃するを押した場合
  if($attackFlg){
      
      //モンスターに攻撃を与える
      History::set($_SESSION['player']->getName().'の攻撃！');
     $_SESSION['player']->attack($_SESSION['monster']);
        debug('マリオが攻撃しました。');
      $_SESSION['monster']->sayCry();

      //キャラクターの叫び
      $_SESSION['player']->sayCry();
      
      //モンスターが攻撃する
      History::set($_SESSION['monster']->getName().'の攻撃!');
      $_SESSION['monster']->attack($_SESSION['player']);
      $_SESSION['player']->sayCry();
      debug('モンスターが攻撃しました。');

//自分のhpが0以下になったらゲームオーバー
if($_SESSION['player']->getHp() <= 0){
    gameOver();
    debug('ゲームオーバーになりました。');
}else{
    //モンスターのhpが0以下になったら、別のモンスターを出現させる
    if($_SESSION['monster']->getHp() <= 0){
        History::set($_SESSION['monster']->getName().'を倒した！');
        debug('モンスターのHPが0になりました。');
        createMonster();
        $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
    }
}
}else{ //逃げる
    History::set('逃げた！');
    debug('マリオが逃げました。');
    createMonster();
}
  }
    $_POST = array();
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>マリオのゲーム｜オブジェクト指向</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<h1 class="gametitle">マリオのゲーム</h1>
<div class="game">
<?php if(empty($_SESSION['history'])){ ?>
<img src="img/wallpaper.jpg" alt="マリオの壁紙">
<form method ="post">
<input class="start" type="submit" name="start" value="Let's Play！">
</form>
<?php }else{?>
<div class="display_container">
<h2 class="enemy"><?php echo $_SESSION['monster']->getName().'が現れた!!';?></h2>
<img id="enemy_image" src="<?php echo $_SESSION['monster']->getImg(); ?>">
    </div>
<div class="status_container">
<p>敵のHP:<?php echo $_SESSION['monster']->getHp();?></p>
<p>倒した敵の数:<?php echo $_SESSION['knockDownCount']; ?></p>
<p>マリオのHP:<?php echo $_SESSION['player']->getHp(); ?></p>
 </div>
 <div class="button">
<form method ="post">
    <input id="select_button" type="submit" name="attack" value="▶︎攻撃する">
    <input id="select_button" type="submit" name="escape" value="▶︎逃げる">
    <input id="select_button" type="submit" name="start" value="▶︎ゲームリスタート">
</form>
</div>
<div class="history_container">
<p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
    </div>
    <?php } ?>
</div>
</body>
</html>
