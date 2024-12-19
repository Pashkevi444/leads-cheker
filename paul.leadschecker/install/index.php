<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/wizard.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/install/wizard_sol/utils.php");

class paul_leadschecker extends CModule
{
    public $MODULE_ID = 'paul.leadschecker';
    public const string LEAD_IDENTIFIER_FIELD_CODE = 'UF_IDENTIFIER';

    public function __construct()
    {
        if (file_exists(__DIR__ . "/version.php")) {
            $arModuleVersion = array();

            include_once(__DIR__ . "/version.php");

            $this->MODULE_ID = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME = Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_NAME");
            $this->MODULE_DESCRIPTION = Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_DESCRIPTION");
            $this->PARTNER_NAME = Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_PARTNER_NAME");
            $this->PARTNER_URI = Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_PARTNER_URI");
        }

        return false;
    }

    /**
     * Method for installing the module.
     * It checks the Bitrix version and registers the module, then installs related events.
     * @throws Exception
     */
    public function DoInstall()
    {
        global $APPLICATION, $messages;

        if (CheckVersion(ModuleManager::getVersion("main"), "01.00.00")) {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->InstallEvents();
            $this->installLeadField();
            $this->optionsSet();

        } else {
            $APPLICATION->ThrowException(
                Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_INSTALL_ERROR_VERSION")
            );
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_INSTALL_TITLE") . " \"" . Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_NAME") . "\"",
            __DIR__ . "/step.php"
        );

        return false;
    }

    /**
     * Method for set options
     * @throws Exception
     */
    public function optionsSet(): void
    {
        $optionMapArray = [
            'IDENTIFIER_USER_PROPERTY_LEAD_CODE' => self::LEAD_IDENTIFIER_FIELD_CODE,
        ];

        if (!empty($optionMapArray)) {
            foreach ($optionMapArray as $key => $value) {
                \COption::SetOptionString($this->MODULE_ID, $key, $value);
            }
        }
    }
    /**
     * Method for uninstalling the module.
     * It unregisters the module and removes related events.
     * @throws Exception
     */
    public function DoUninstall()
    {
        global $APPLICATION;

        ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallEvents();
        $this->unInstallLeadField();
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_UNINSTALL_TITLE") . " \"" . Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_NAME") . "\"",
            __DIR__ . "/unstep.php"
        );

        return false;
    }

    /**
     * Method for registering event handlers.
     * @return bool
     */
    public function InstallEvents()
    {
        try {
            global $APPLICATION;
            $eventManager = Bitrix\Main\EventManager::getInstance();
            $eventManager->registerEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "Paul\\Events\\Module",
                "OnBeforeProlog");
            $eventManager->registerEventHandler("crm", "OnBeforeCrmLeadAdd", $this->MODULE_ID, "Paul\\Events\\Module",
                "checkLeadIdentifierOnAdd");
            $eventManager->registerEventHandler("crm", "OnBeforeCrmLeadUpdate", $this->MODULE_ID,
                "Paul\\Events\\Module",
                "checkLeadIdentifierOnUpdate");

            return true;
        } catch (\Exception $e) {
            \Bitrix\Main\Diag\Debug::writeToFile($e->getMessage(), '', '/bitrix/log/install_log.txt');
            $APPLICATION->ThrowException(
                Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_INSTALL_ERROR_INSTALL_EVENTS") . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Method for unregistering event handlers.
     * Removes the previously registered "OnBeforeProlog" event handler.
     * @return bool
     */
    public function UnInstallEvents()
    {
        try {
            global $APPLICATION;
            $eventManager = Bitrix\Main\EventManager::getInstance();
            $eventManager->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "Paul\\Events\\Module",
                "OnBeforeProlog");
            $eventManager->unRegisterEventHandler("crm", "OnBeforeCrmLeadAdd", $this->MODULE_ID, "Paul\\Events\\Module",
                "checkLeadIdentifierOnAdd");
            $eventManager->unRegisterEventHandler("crm", "OnBeforeCrmLeadUpdate", $this->MODULE_ID,
                "Paul\\Events\\Module",
                "checkLeadIdentifierOnUpdate");

            return true;
        } catch (\Exception $e) {
            \Bitrix\Main\Diag\Debug::writeToFile($e->getMessage(), '', '/bitrix/log/install_log.txt');
            $APPLICATION->ThrowException(
                Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_INSTALL_ERROR_DELETE_EVENTS") . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Method for installing the custom field "Identifier" in the "Lead" entity.
     * If the field already exists, it will not be created again.
     * @throws Exception
     */
    public function installLeadField(): void
    {
        try {
            global $APPLICATION;
            $existingField = \CUserTypeEntity::GetList(
                [],
                ['ENTITY_ID' => 'CRM_LEAD', 'FIELD_NAME' => self::LEAD_IDENTIFIER_FIELD_CODE]
            )->Fetch();

            if (!$existingField) {
                $fields = [
                    'ENTITY_ID' => 'CRM_LEAD',
                    'FIELD_NAME' => self::LEAD_IDENTIFIER_FIELD_CODE,
                    'USER_TYPE_ID' => 'string',
                    'XML_ID' => 'IDENTIFIER',
                    'SORT' => 100,
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'Y',
                    'SHOW_FILTER' => 'I',
                    'SHOW_IN_LIST' => 'Y',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'Y',
                    'EDIT_FORM_LABEL' => [
                        'en' => 'Identifier',
                        'ru' => 'Идентификатор',
                    ],
                    'LIST_COLUMN_LABEL' => [
                        'en' => 'Identifier',
                        'ru' => 'Идентификатор',
                    ],
                    'LIST_FILTER_LABEL' => [
                        'en' => 'Identifier',
                        'ru' => 'Идентификатор',
                    ],
                    'ERROR_MESSAGE' => [
                        'en' => 'Incorrect identifier format',
                        'ru' => 'Некорректный формат идентификатора',
                    ],
                    'HELP_MESSAGE' => [
                        'en' => 'Enter the identifier in the format ABCD-1234',
                        'ru' => 'Введите идентификатор в формате ABCD-1234',
                    ],
                ];

                if (!(new \CUserTypeEntity())->Add($fields)) {
                    throw new \Exception('Failed to add user field "Identifier".');
                }
            }
        } catch (\Exception $e) {
            \Bitrix\Main\Diag\Debug::writeToFile($e->getMessage(), '', '/bitrix/log/install_log.txt');
            $APPLICATION->ThrowException(
                Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_INSTALL_ERROR_INSTALL_FIELDS") . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Method for uninstalling the custom field "Identifier" from the "Lead" entity.
     * This field will be deleted if it exists.
     * @throws Exception
     */
    public function unInstallLeadField(): void
    {
        try {
            global $APPLICATION;
            $existingField = \CUserTypeEntity::GetList(
                [],
                ['ENTITY_ID' => 'CRM_LEAD', 'FIELD_NAME' => self::LEAD_IDENTIFIER_FIELD_CODE]
            )->Fetch();

            if ($existingField) {
                if (!(new \CUserTypeEntity())->Delete($existingField['ID'])) {
                    throw new \Exception('Failed to delete user field "Identifier".');
                }
            }
        } catch (\Exception $e) {
            \Bitrix\Main\Diag\Debug::writeToFile($e->getMessage(), '', '/bitrix/log/uninstall_log.txt');
            $APPLICATION->ThrowException(
                Loc::getMessage("PAUL_LEADCHECKER_SETTINGS_INSTALL_ERROR_DELETE_FIELDS") . ': ' . $e->getMessage()
            );
        }
    }



}
