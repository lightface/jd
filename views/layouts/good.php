<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/17
 * Time: 10:20
 */
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use app\assets\GoodAsset;

GoodAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    if(!empty($this->params['refresh'])){ ?>
        <meta http-equiv="refresh" content="<?php echo $this->params['refresh'] ?>">
    <?php    }
    ?>
    <title><?php echo isset($this->title) ? $this->title : '' ?></title>
    <script src="/jd/web/assets/645913cf/jquery.min.js"></script>
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <div class="container">
        <?= $content ?>
    </div>
</div>

<footer class="footer" >
    <div class="container" style="text-align: center" >
        <span>京东商品各种抓</span>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
