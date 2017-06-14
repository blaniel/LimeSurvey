<?php

namespace ls\tests;

use PHPUnit\Framework\TestCase;

class DummyController extends \LSYii_Controller
{
}

/**
 * @since 2017-06-13
 */
class DateTimeForwardBackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestHelper
     */
    protected static $testHelper = null;

    /**
     * @var int
     */
    public static $surveyId = null;

    /**
     * Import survey in tests/surveys/.
     */
    public static function setupBeforeClass()
    {
        \Yii::import('application.helpers.common_helper', true);
        \Yii::import('application.helpers.replacements_helper', true);
        \Yii::import('application.helpers.surveytranslator_helper', true);
        \Yii::import('application.helpers.admin.import_helper', true);
        \Yii::import('application.helpers.expressions.em_manager_helper', true);
        \Yii::import('application.helpers.expressions.em_manager_helper', true);
        \Yii::import('application.helpers.qanda_helper', true);
        \Yii::app()->loadHelper('admin/activate');

        \Yii::app()->session['loginID'] = 1;

        self::$testHelper = new TestHelper();

        $surveyFile = __DIR__ . '/../data/surveys/limesurvey_survey_917744.lss';
        if (!file_exists($surveyFile)) {
            die('Fatal error: found no survey file');
        }

        $translateLinksFields = false;
        $newSurveyName = null;
        $result = importSurveyFile(
            $surveyFile,
            $translateLinksFields,
            $newSurveyName,
            null
        );
        if ($result) {
            self::$surveyId = $result['newsid'];
        } else {
            die('Fatal error: Could not import survey');
        }
    }

    /**
     * Destroy what had been imported.
     */
    public static function teardownAfterClass()
    {
        $result = \Survey::model()->deleteSurvey(self::$surveyId, true);
        if (!$result) {
            die('Fatal error: Could not clean up survey ' . self::$surveyId);
        }
    }

    /**
     * q1 is hidden question with default answer "now".
     * @group q01
     */
    public function testQ1()
    {
        $this->markTestSkipped('How to unit-test qanda?');
        return;

        list($question, $group, $sgqa) = self::$testHelper->getSgqa('G1Q00001', self::$surveyId);
        $surveyMode = 'group';
        $LEMdebugLevel = 0;
        $survey = \Survey::model()->findByPk(self::$surveyId);

        $survey->anonymized = '';
        $survey->datestamp = '';
        $survey->ipaddr = '';
        $survey->refurl = '';
        $survey->savetimings = '';
        $survey->save();
        \Survey::model()->resetCache();  // Make sure the saved values will be picked up

        $result = \activateSurvey(self::$surveyId);

        // Must fetch this AFTER survey is activated.
        $surveyOptions = self::$testHelper->getSurveyOptions(self::$surveyId);

        $this->assertEquals(['status' => 'OK'], $result, 'Activate survey is OK');

        \Yii::app()->setConfig('surveyID', self::$surveyId);
        \Yii::app()->setController(new DummyController(1));
        buildsurveysession(self::$surveyId);
        $result = \LimeExpressionManager::StartSurvey(
            self::$surveyId,
            $surveyMode,
            $surveyOptions,
            false,
            $LEMdebugLevel
        );
        $this->assertEquals(
            [
                'hasNext' => 1,
                'hasPrevious' => null
            ],
            $result
        );

        $qid = $question->qid;
        $gseq = 0;
        $_POST['relevance' . $qid] = 1;
        $_POST['relevanceG' . $gseq] = 1;
        $_POST['lastgroup'] = self::$surveyId . 'X' . $group->gid;
        $_POST['movenext'] = 'movenext';
        $_POST['thisstep'] = 1;
        $_POST['sid'] = self::$surveyId;
        $_POST[$sgqa] = '10:00';

        $moveResult = \LimeExpressionManager::NavigateForwards();
        $result = \LimeExpressionManager::ProcessCurrentResponses();

        $moveResult = \LimeExpressionManager::NavigateForwards();
        $result = \LimeExpressionManager::ProcessCurrentResponses();

        $moveResult = \LimeExpressionManager::NavigateBackwards();
        $result = \LimeExpressionManager::ProcessCurrentResponses();
        $moveResult = \LimeExpressionManager::NavigateForwards();
        $moveResult = \LimeExpressionManager::NavigateForwards();
        print_r($moveResult);
        $result = \LimeExpressionManager::ProcessCurrentResponses();
        print_r($result);
        print_r($_POST);
        //$this->assertEquals('10:00', $_SESSION['survey_' . self::$surveyId][$sgqa]);

        $query = 'SELECT * FROM lime_survey_' . self::$surveyId;
        print_r($query);
        $result = \Yii::app()->db->createCommand($query)->queryAll();
        print_r($result);
        $qanda = \retrieveAnswers(
            $_SESSION['survey_' . self::$surveyId]['fieldarray'][0],
            self::$surveyId
        );
        print_r($qanda);
        print_r($_SESSION['survey_' . self::$surveyId][$sgqa]);



        $surveyId = self::$surveyId;
        $date     = date('YmdHis');

        // Deactivate survey.
        $oldSurveyTableName = \Yii::app()->db->tablePrefix."survey_{$surveyId}";
        $newSurveyTableName = \Yii::app()->db->tablePrefix."old_survey_{$surveyId}_{$date}";
        \Yii::app()->db->createCommand()->renameTable($oldSurveyTableName, $newSurveyTableName);
        $survey = \Survey::model()->findByPk($surveyId);
        $survey->active = 'N';
        $survey->save();
    }

}
