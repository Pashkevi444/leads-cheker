<?php


\Bitrix\Main\Loader::registerAutoLoadClasses(
    "paul.leadschecker",
    array(
        'Paul\\Events\\Module' => 'lib/events/Module.php',
        'Paul\\OptionsData' => 'lib/OptionsData.php',
    )
);
