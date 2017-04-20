<?php

namespace Backend\Modules\FormBuilder\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\DataGridDB as BackendDataGridDB;
use Backend\Modules\FormBuilder\Engine\Model as BackendFormBuilderModel;

/**
 * This is the index-action (default), it will display the overview
 */
class Index extends BackendBaseActionIndex
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();
        $this->loadDataGrid();
        $this->parse();
        $this->display();
    }

    /**
     * Load the datagrids
     */
    private function loadDataGrid()
    {
        $this->dataGrid = new BackendDataGridDB(
            BackendFormBuilderModel::QRY_BROWSE,
            BL::getWorkingLanguage()
        );
        $this->dataGrid->setHeaderLabels(array(
            'email' => \SpoonFilter::ucfirst(BL::getLabel('Recipient')),
            'sent_forms' => '',
        ));
        $this->dataGrid->setSortingColumns(array('name', 'email', 'method', 'sent_forms'), 'name');
        $this->dataGrid->setColumnFunction(
            array(new BackendFormBuilderModel(), 'formatRecipients'),
            array('[email]'),
            'email'
        );
        $this->dataGrid->setColumnFunction(
            array(__CLASS__, 'parseNumForms'),
            array('[id]', '[sent_forms]', '[method]'),
            'sent_forms'
        );
        $this->dataGrid->setColumnFunction(
            array(new BackendFormBuilderModel(), 'getLocale'),
            array('Method_[method]'),
            'method'
        );

        // check if edit action is allowed
        if (BackendAuthentication::isAllowedAction('Edit')) {
            $this->dataGrid->setColumnURL(
                'name',
                BackendModel::createURLForAction('Edit') . '&amp;id=[id]'
            );
            $this->dataGrid->addColumn(
                'edit',
                null,
                BL::getLabel('Edit'),
                BackendModel::createURLForAction('Edit') . '&amp;id=[id]',
                BL::getLabel('Edit')
            );
        }
    }

    /**
     * Parse the datagrid and the reports
     */
    protected function parse()
    {
        parent::parse();

        // add datagrid
        $this->tpl->assign('dataGrid', (string) $this->dataGrid->getContent());
    }

    /**
     * Parse amount of forms sent for the datagrid
     *
     * @param int $formId Id of the form.
     * @param int $sentForms Amount of sent forms.
     * @param string $method The way the data is handled.
     *
     * @return string
     */
    public static function parseNumForms(int $formId, int $sentForms, string $method): string
    {
        if ($method === 'email') {
            return '';
        }

        // one form sent
        if ($sentForms === 1) {
            $output = BL::getMessage('OneSentForm');
        } elseif ($sentForms > 1) {
            // multiple forms sent
            $output = sprintf(BL::getMessage('SentForms'), $sentForms);
        } else {
            // no forms sent
            $output = sprintf(BL::getMessage('SentForms'), $sentForms);
        }

        // check if data action is allowed
        if (BackendAuthentication::isAllowedAction('Data', 'FormBuilder')) {
            // output
            $output = '<a href="' . BackendModel::createURLForAction('Data') .
                      '&amp;id=' . $formId . '" title="' . $output . '">' . $output . '</a>';
        }

        return $output;
    }
}
