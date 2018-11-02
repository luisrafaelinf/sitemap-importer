<?php

namespace luisrafaelinf\SitemapImporter\Command;

use Snowdog\DevTest\Core\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

use luisrafaelinf\SitemapImporter\Component\Importer;
use luisrafaelinf\SitemapImporter\ConstantImporter\ConstImporterXML;

class SitemapCommand
{
    /**
     * @var QuestionHelper
     */
    private $helper;

    /**
     * @var Database
     */
    private $database;

    public function __construct(QuestionHelper $helper, Database $database)
    {
        $this->helper = $helper;
        $this->database = $database;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Please provide path to XML sitemap:');

        $file = $this->helper->ask($input, $output, $question);

        try {

            $importer = new Importer($file, $this->database);
            $user = $input->getArgument('user_login');

            if (!$user) {
                $output->writeln('<error>User not found.</error>');
                return false;
            }
            $result = $importer->import($input->getArgument('user_login'));
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln('Added <info>' . $result[ConstImporterXML::WEBSITES] . '</info> websites');
        $output->writeln('Added <info>' . $result[ConstImporterXML::PAGES] . '</info> pages');
    }

}
