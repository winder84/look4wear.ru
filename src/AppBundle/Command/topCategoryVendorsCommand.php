<?php

namespace AppBundle\Command;

use AppBundle\Entity\Category;
use AppBundle\Entity\Goods;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class topCategoryVendorsCommand extends ContainerAwareCommand
{

    /**
     * @var OutputInterface
     */
    protected static $output;

    /**
     * @var string Разделитель
     */
    protected static $delimer = '----------';

    /**
     * @var EntityManager
     */
    protected static $em;

    /**
     * @var Goods
     */
    protected static $goods = null;

    /**
     * @var array
     */
    protected static $goodsGroupIds = [];

    /**
     * @var string
     */
    protected static $externalId = '';

    /**
     * @var string
     */
    protected static $aliasName = '';

    /**
     * @var string
     */
    protected static $goodsType = '';

    /**
     * @var array
     */
    protected static $paramsArray = [];

    /**
     * @var string
     */
    protected static $paramName = '';

    /**
     * @var array
     */
    protected static $pictures = [];

    /** @var  string */
    protected static $tmpFilePath;

    protected static $ctx;

    protected function configure()
    {
        $this
            ->setName('main:topCategoryVendors')
            ->setDescription('Get top 20 category vendors')
            ->addArgument(
                'categoryId',
                InputArgument::OPTIONAL,
                'What category?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$em = $this->getContainer()->get('doctrine')->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        self::$output = $output;
        self::$tmpFilePath = '/tmp/tmpFile.xml';
        $this->outputWriteLn('Получаем топ20 брендов для категорий.');
        $categoryId = intval($input->getArgument('categoryId'));
        if ($categoryId) {
            /** @var Category $category */
            $category = self::$em
                ->getRepository('AppBundle:Category')
                ->findOneBy([
                    'id' => $categoryId
                ]);
            $categories = [$category];
        } else {
            $categories = self::$em
                ->getRepository('AppBundle:Category')
                ->findAll();
        }
        foreach ($categories as $category) {
            $this->saveTopVendorsForCategory($category);
        }
        $this->outputWriteLn('Топ 20 брендов для категорий получен и записан.');
    }

    /**
     * @param $category Category
     */
    private function saveTopVendorsForCategory($category)
    {
        if ($category) {
            $excludeWords = explode(';', $category->getExcludeWords());
            $searchString = $category->getSearchString();
            if (array_filter($excludeWords)) {
                $searchString .= ' -' . implode(' -', $excludeWords);
            }
            $searchGoods = $this->searchByString($searchString, 1);
            $totalCount = $searchGoods['total_found'];
            $totalCountIndex = 0;
            $i = 1;
            $categoryVendors = [];
            while ($totalCount > $totalCountIndex) {
                $totalCountIndex = $i * 10000;
                $searchGoodsForVendors = $this->searchByString($searchString, 10000);
                if (isset($searchGoodsForVendors['matches'])) {
                    $matchesForVendors = $searchGoodsForVendors['matches'];
                    foreach ($matchesForVendors as $matchForVendors) {
                        $vendorAlias = $matchForVendors['attrs']['vendoralias'];
                        if (isset($categoryVendors[$vendorAlias])) {
                            $categoryVendors[$vendorAlias] += 1;
                        } else {
                            $categoryVendors[$vendorAlias] = 0;
                        }
                    }
                }
                $i++;
            }
            arsort($categoryVendors);
            $categoryVendors = array_slice($categoryVendors, 0, 20);
            $categoryData = $category->getData();
            if (!$categoryData) {
                $categoryData = [];
            }
            $categoryData['topVendors'] = $categoryVendors;
            $category->setData($categoryData);
            self::$em->persist($category);
            self::$em->flush();
            $this->outputWriteLn('Категория "' . $category->getName() . '" обработана.');
        }
    }
    /**
     * Метод выводит текст в консоль, добавляет время и количество съедаемой памяти
     * @param $text string Текст для вывода в консоль
     */
    private function outputWriteLn($text)
    {
        $style = new OutputFormatterStyle('red', null, ['bold', 'blink']);
        self::$output->getFormatter()->setStyle('red', $style);
        $style = new OutputFormatterStyle('blue', null, ['bold', 'blink']);
        self::$output->getFormatter()->setStyle('blue', $style);
        $style = new OutputFormatterStyle('yellow', null, ['bold', 'blink']);
        self::$output->getFormatter()->setStyle('yellow', $style);
        $newTimeDate = new \DateTime();
        $newTimeDate = $newTimeDate->format(\DateTime::ATOM);
        self::$output->writeln(self::$delimer . $newTimeDate . ' | <blue>' . $text . '</blue> | Memory usage: <red>' . round(memory_get_usage() / (1024 * 1024)) . ' MB</red>' . self::$delimer);
    }

    /**
     * @param $searchString
     * @param $limit
     * @return mixed
     */
    private function searchByString($searchString, $limit)
    {
        $sphinxSearch = $this->getContainer()->get('iakumai.sphinxsearch.search');
        $sphinxSearch->setLimits(0, $limit, 100000);
        $sphinxSearch->SetMatchMode(SPH_MATCH_EXTENDED);
        return $sphinxSearch->query($searchString);
    }
}