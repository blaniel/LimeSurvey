<?php

namespace LimeSurvey\Helpers\Update;

class Update_479 extends DatabaseUpdateBase
{
    public function up()
    {
        $baseQuestionThemeEntries = LsDefaultDataSets::getBaseQuestionThemeEntries();
        $this->db->createCommand()->update("{{question_themes}}", ['name' => 'bootstrap_buttons_multi'], "name='bootstrap_buttons' and extends='M'");
        foreach ($baseQuestionThemeEntries as $baseQuestionThemeEntry) {
            unset($baseQuestionThemeEntry['visible']);
            $this->db->createCommand()->update("{{question_themes}}", $baseQuestionThemeEntry, 'name=:name', [':name' => $baseQuestionThemeEntry['name']]);
        }
    }
}
