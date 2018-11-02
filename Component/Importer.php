<?php

namespace luisrafaelinf\SitemapImporter\Component;

use Snowdog\DevTest\Core\Database;
use Snowdog\DevTest\Model\User;
use Snowdog\DevTest\Model\UserManager;
use Snowdog\DevTest\Model\Website;
use Snowdog\DevTest\Model\WebsiteManager;
use Snowdog\DevTest\Model\Page;
use Snowdog\DevTest\Model\PageManager;

use luisrafaelinf\SitemapImporter\ConstantImporter\ConstImporterXML;

class Importer
{
    /**
     * @var Sitemap
     */
    private $sitemap;

    /**
     * @var Database
     */
    private $database;

    public function __construct($pathFile, Database $database)
    {
        if (!extension_loaded(ConstImporterXML::MODULE)) {
            throw new \Exception('Simple Importer module not loaded.', 100);
        }

        if (!file_exists($pathFile)) {
            throw new \Exception('File not found.', 101);
        }

        $this->sitemap = simplexml_load_file($pathFile);
        $this->database = $database;
    }

    public function import($userLogin)
    {
        $data = $this->getInfo();
        if (empty($data)) {
            throw new \Exception('Incorrect data in sitemap file.', 102);
        }

        $importPagesCount = 0;
        $importWebsitesCount = 0;
        $userManager = new UserManager($this->database);
        $websiteManager = new WebsiteManager($this->database);
        $pageManager = new PageManager($this->database);

        $user = $userManager->getByLogin($userLogin);
        if (!$user instanceof User) {
            throw new \Exception('User does not exists.', 103);
        }

        foreach ($data as $url => $pages) {

            $website = $websiteManager->getByHostname($url);

            if (!$website instanceof Website) {
\var_dump($url);
                $websiteId = $websiteManager->create($user, $url, $url);
                $website = $websiteManager->getById($websiteId);
                $importWebsitesCount++;
            }

            foreach ($pages as $pageImport) {

                $page = $pageManager->getByUrl($pageImport);

                if (!$page instanceof Page) {

                    $pageManager->create($website, $pageImport);
                    $importPagesCount++;
                }
            }
        }

        return [
            ConstImporterXML::WEBSITES => $importWebsitesCount,
            ConstImporterXML::PAGES => $importPagesCount
        ];
    }

    private function getInfo()
    {
        $result = [];
        foreach ($this->sitemap->url as $element) {
            $pagePath = parse_url($element->loc, PHP_URL_PATH);
            $pageQuery = parse_url($element->loc, PHP_URL_QUERY);
            $pageFragment = parse_url($element->loc, PHP_URL_FRAGMENT);

            if ($pagePath != '/') {
                $pagePath = substr($pagePath, 1);
            }
            $page = $pagePath . ($pageQuery ? '?' . $pageQuery : '') . ($pageFragment ? '#' . $pageFragment : '');

            $result[parse_url($element->loc, PHP_URL_HOST)][] = $page;
        }

        return $result;
    }
}
