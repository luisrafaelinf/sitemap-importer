<?php

namespace luisrafaelinf\SitemapImporter\Controller;

use Snowdog\DevTest\Core\Database;
use Snowdog\DevTest\Model\User;
use Snowdog\DevTest\Model\UserManager;
use Snowdog\DevTest\Controller\AbstractController\ForbiddenAbstract;
use Snowdog\DevTest\Constant\SessionValue;

use luisrafaelinf\SitemapImporter\Component\Importer;
use luisrafaelinf\SitemapImporter\ConstantImporter\ConstImporterXML;

class ImporterAction
{

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var Database
     */
    private $database;

    public function __construct(Database $database, UserManager $userManager)
    {
        $this->database = $database;
        $this->userManager = $userManager;
    }

    public function execute()
    {
        if (!isset($_SESSION[SessionValue::LOGIN])) {
            return http_response_code('403');
        }

        $user = $this->userManager->getByLogin($_SESSION[SessionValue::LOGIN]);

        if (!$user) {
            return http_response_code('403');
        }

        $sitemapFile = $_FILES[ConstImporterXML::SITEMAP];

        if (
            !isset($sitemapFile[ConstImporterXML::ERROR]) ||
            is_array($sitemapFile[ConstImporterXML::ERROR])
        ) {
            throw new \Exception('invalid parameters');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        if (false === $ext = array_search(
                $finfo->file($sitemapFile[ConstImporterXML::TEMPORAL_NAME]),
                array('xml' => 'text/xml'),
                true
            ))
        {
            $_SESSION['flash'] = 'Only XML files are allowed.';
            return header('Location: /');
        }

        $uploadDir = WEB_DIR . '/uploads/';
        $uploadFile = $uploadDir . basename($sitemapFile[ConstImporterXML::NAME]);

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0744);
        }

        if (!move_uploaded_file($sitemapFile[ConstImporterXML::TEMPORAL_NAME], $uploadFile)) {
            throw new \Exception('moving file failed.');
        }

        try {
            $sitemapImporter = new Importer($uploadFile, $this->database);
            $result = $sitemapImporter->import($user->getLogin());
        } catch (\Exception $e) {
            $_SESSION['flash'] = 'Exception occurred while importing sitemap, check content correctness';
        }

        if ($result[ConstImporterXML::WEBSITES] || $result[ConstImporterXML::PAGES]) {
            $_SESSION['flash'] = 'Imported ' . $result[ConstImporterXML::WEBSITES] . ' websites and ' .
                $result[ConstImporterXML::PAGES] . ' pages finished.';
        } else {
            $_SESSION['flash'] = 'Finished import.';
        }

        return header('Location: /');
    }
}
