<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoloadaf93c4d51482bdad4ce725be5e3beba3($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'svnplugin' => '/svnPlugin.class.php',
            'svnplugindescriptor' => '/SvnPluginDescriptor.class.php',
            'svnplugininfo' => '/SvnPluginInfo.class.php',
            'tuleap\\svn\\codebrowser\\codebrowsercontroller' => '/Svn/CodeBrowser/CodeBrowserController.class.php',
            'tuleap\\svn\\explorer\\explorercontroller' => '/Svn/Explorer/ExplorerController.class.php',
            'tuleap\\svn\\presenter\\codebrowserpresenter' => '/Svn/Presenter/CodeBrowserPresenter.class.php',
            'tuleap\\svn\\presenter\\explorerpresenter' => '/Svn/Presenter/ExplorerPresenter.class.php',
            'tuleap\\svn\\servicesvn' => '/Svn/ServiceSvn.class.php',
            'tuleap\\svn\\svnrouter' => '/Svn/SvnRouter.class.php',
            'tuleap\\svn\\viewvcproxy\\viewvcproxy' => '/Svn/ViewVCProxy/ViewVCProxy.class.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoloadaf93c4d51482bdad4ce725be5e3beba3');
// @codeCoverageIgnoreEnd
