<?php

use app\common\grid\ActionColumn;
use app\common\grid\SerialColumn;
use app\common\helpers\RenderHelper;
use app\common\widgets\DateRangePicker;
use app\common\widgets\JsBlock;
use app\models\Music;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var $dataProvider yii\data\ActiveDataProvider
 * @var $searchModel app\models\search\MusicSearch
 */

$this->title = '我的音乐';
$this->params['breadcrumbs'][] = ['label' => '音乐', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['my-music']];

$gridColumns = [
    ['class' => SerialColumn::className()],

    [
        'attribute' => 'track_title',
        'value' => function ($model) {
            return Html::a($model->track_title, 'javascript:void(0)', [
                'class' => 'playlist',
                'style' => ['display' => 'block'],
                'data-music' => $model->music_file,
            ]);
        },
        'format' => 'raw',
    ],
    [
        'attribute' => 'visible',
        'value' => function ($model) {
            /** @var $model Music */
            return Music::$visible_map[$model->visible];
        },
        'filter' => RenderHelper::dropDownFilter($searchModel, 'visible', Music::$visible_map),
        'headerOptions' => ['width' => 100],
    ],
    [
        'attribute' => 'status',
        'value' => function ($model) {
            /** @var $model Music */
            return Music::$status_map[$model->status];
        },
        'filter' => RenderHelper::dropDownFilter($searchModel, 'status', Music::$status_map),
        'headerOptions' => ['width' => 100],
    ],
    [
        'attribute' => 'created_at',
        'format' => 'dateTime',
        'filterType' => DateRangePicker::className(),
        'headerOptions' => ['width' => 160],
    ],
    [
        'attribute' => 'updated_at',
        'format' => 'dateTime',
        'filterType' => DateRangePicker::className(),
        'headerOptions' => ['width' => 160],
    ],

    [
        'class' => ActionColumn::className(),
        'template' => '{update} {delete}',
    ],
];
?>
<div class="music-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <hr>

    <h2 id="track_title">点击标题播放音乐</h2>

    <audio id="player" src="" controls></audio>

    <hr>

    <p>
        <?= Html::a('添加音乐', ['create'], ['class' => 'btn btn-info']) ?>
    </p>

    <?= RenderHelper::gridView($dataProvider, $gridColumns, $searchModel) ?>

</div>
<?php JsBlock::begin(); ?>
<script>
    $(".playlist").click(function () {
        $("#track_title").html($(this).html());
        var player = $("#player")[0];
        var musicSrc = "<?= Music::getMusicPathUrl() ?>" + $(this).data("music");
        if (player.src == musicSrc && !player.paused) {
            player.pause();
        } else if (player.src == musicSrc && player.paused) {
            player.play();
        } else {
            player.src = musicSrc;
            player.play();
        }
    });
</script>
<?php JsBlock::end(); ?>
