<?php

namespace app\modules\frontend\controllers;

use app\common\helpers\Message;
use app\common\helpers\UserHelper;
use app\models\Music;
use app\models\search\MusicSearch;
use app\modules\frontend\controllers\base\ModuleController;
use app\modules\frontend\models\MusicForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * MusicController implements the CRUD actions for Music model.
 */
class MusicController extends ModuleController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                        ],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'my-music',
                            'create',
                            'update',
                            'delete',
                        ],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Music models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new MusicSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionMyMusic()
    {
        $searchModel = new MusicSearch();
        $dataProvider = $searchModel->searchMyMusic(Yii::$app->request->queryParams);

        return $this->render('my-music', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Music model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $form = new MusicForm();
        $form->scenario = 'create';
        if ($form->load(Yii::$app->request->post())) {
            $form->music_file = UploadedFile::getInstance($form, 'music_file');
            if ($form->validate()) {
                $model = new Music();
                $model->setAttributes($form->getAttributes());
                if ($model->uploadMusic($form->music_file)) {
                    if ($model->save(false)) {
                        Message::setSuccessMsg('上传成功');
                        return $this->redirect(['index']);
                    } else {
                        $model->deleteMusic();
                        Message::setErrorMsg('上传失败');
                    }
                } else {
                    $form->addError('music_file', '文件上传失败');
                }
            }
        }

        return $this->render('create', [
            'model' => $form,
        ]);
    }

    /**
     * Updates an existing Music model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (!UserHelper::isBelongToUser($model->user_id)) {
            Message::setErrorMsg('不可修改其他人的数据');
            return $this->redirect(['index']);
        }

        $form = new MusicForm();
        $form->scenario = 'update';
        if ($form->load(Yii::$app->request->post())) {
            $form->music_file = UploadedFile::getInstance($form, 'music_file');
            if ($form->validate()) {
                $original_file_name = $model->music_file;//记录原文件名
                $model->setAttributes($form->getAttributes(null, ['music_file']));
                $flow = true;

                //如果上传了文件，上传新文件
                if ($form->music_file) {
                    if (!$model->uploadMusic($form->music_file)) {
                        $form->addError('music_file', '文件上传失败');
                        $flow = false;
                    }
                }

                if ($flow) {
                    if ($model->save(false)) {
                        //如果上传了新文件，删除原文件
                        if ($form->music_file) {
                            unlink(Music::getMusicFullPath($original_file_name));
                        }
                        Message::setSuccessMsg('修改成功');
                        return $this->redirect(['index']);
                    } else {
                        //如果上传了新文件，删除新文件
                        if ($form->music_file) {
                            $model->deleteMusic();
                        }
                        Message::setErrorMsg('修改失败');
                    }
                }
            }
        } else {
            $form->setAttributes($model->getAttributes(null, ['music_file']));
        }

        return $this->render('update', [
            'model' => $form,
        ]);
    }

    /**
     * Deletes an existing Music model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (UserHelper::isBelongToUser($model->user_id)) {
            if ($model->delete()) {
                $model->deleteMusic();
                Message::setSuccessMsg('删除成功');
            } else {
                Message::setErrorMsg('删除失败');
            }
        } else {
            Message::setErrorMsg('不能删除其他人的数据');
        }

        return $this->redirect(['my-music']);
    }

    /**
     * Finds the Music model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Music the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Music::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
