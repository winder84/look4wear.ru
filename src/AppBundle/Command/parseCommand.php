<?php

namespace AppBundle\Command;
use AppBundle\Entity\Goods;
use AppBundle\Entity\Offer;
use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Acl\Exception\Exception;

class parseCommand extends ContainerAwareCommand
{

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string Разделитель
     */
    protected $delimer = '----------';

    /**
     * @var EntityManager
     */
    protected $em;

    protected function configure()
    {
        $this
            ->setName('main:parse')
            ->setDescription('Parse offers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->output = $output;
        $offers = $this->em
            ->getRepository('AppBundle:Offer')
            ->findAll();
        foreach ($offers as $offer) {
            $this->parseOffer($offer);
        }
    }

    /**
     * Метод выводит текст в консоль, добавляет время и количество съедаемой памяти
     * @param $text string Текст для вывода в консоль
     */
    private function outputWriteLn($text)
    {
        $style = new OutputFormatterStyle('red', null, array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('red', $style);
        $style = new OutputFormatterStyle('blue', null, array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('blue', $style);
        $newTimeDate = new \DateTime();
        $newTimeDate = $newTimeDate->format(\DateTime::ATOM);
        $this->output->writeln($this->delimer. $newTimeDate . ' | <blue>' . $text . '</blue> | Memory usage: <red>' . round(memory_get_usage() / (1024 * 1024)) . ' MB</red>' . $this->delimer);
    }

    /**
     * @param $offer Offer
     */
    private function parseOffer($offer)
    {
        $version = $offer->getVersion();
        $version++;
        $this->outputWriteLn('Начало парсинга оффера ' . $offer->getName());
        $offerXmlUrl = $offer->getXmlParseUrl();
        $xmlReader = \XMLReader::open($offerXmlUrl);
        $resultArray = array();
        $countIndex = 0;
        $checked = true;
        try {
            while($xmlReader->read());
        } catch (\Exception $e) {
            $checked = false;
            $this->outputWriteLn('Ошибка: ' . $e->getMessage());
        }
        if ($checked) {
            $xmlReader = \XMLReader::open($offerXmlUrl);
            while($xmlReader->read()) {
                if($xmlReader->nodeType == \XMLReader::ELEMENT) {
                    if($xmlReader->localName == 'offer') {
                        $groupId = $xmlReader->getAttribute('group_id');
                        $externalId = $xmlReader->getAttribute('id');
                        $oldGoodsByGroupId = null;
                        if ($groupId) {
                            $oldGoodsByGroupId = $this->em
                                ->getRepository('AppBundle:Goods')
                                ->findOneBy(array(
                                    'groupId' => $groupId,
                                    'Offer' => $offer,
                                ));
                        }
                        if ($oldGoodsByGroupId) {
                            $oldGoods = $oldGoodsByGroupId;
                        } else {
                            $oldGoods = $this->em
                                ->getRepository('AppBundle:Goods')
                                ->findOneBy(array(
                                    'externalId' => $externalId,
                                    'Offer' => $offer,
                                ));
                        }
                        if ($oldGoods) {
                            do {
                                $xmlReader->read();
                                if ($xmlReader->nodeType != \XMLReader::END_ELEMENT) {
                                    $tagName = $xmlReader->localName;
                                    $xmlReader->read();
                                    $value = $xmlReader->value;
                                    print_r($tagName . ' - ' . $value . "\n");
                                }
                            } while ($xmlReader->localName != 'offer');
                        } else {
                            $newGoods = new Goods();
                            $newGoods->setExternalId($externalId);
                            $aliasName = '';
                            do {
                                $xmlReader->read();
                                if ($xmlReader->nodeType != \XMLReader::END_ELEMENT) {
                                    $tagName = $xmlReader->localName;
                                    $xmlReader->read();
                                    $value = $xmlReader->value;
                                    switch ($tagName) {
                                        case 'currencyId':
                                            $newGoods->setCurrency($value);
                                            break;
                                        case 'description':
                                            $newGoods->setDescription($value);
                                            break;
                                        case 'market_category':
                                            $newGoods->setCategory($value);
                                            break;
                                        case 'model':
                                            $newGoods->setModel($value);
                                            break;
                                        case 'name':
                                            $aliasName = $externalId . '_' . $value;
                                            $newGoods->setName($value);
                                            break;
                                        case 'price':
                                            $newGoods->setPrice(floatval($value));
                                            break;
                                        case 'oldprice':
                                            $newGoods->setOldPrice(floatval($value));
                                            break;
                                        case 'url':
                                            $newGoods->setUrl($value);
                                            break;
                                        case 'vendorCode':
                                            $newGoods->setVendorCode($value);
                                            break;
                                        case 'vendor':
                                            $vendorAlias = $vendorName = iconv("UTF-8", "UTF-8//IGNORE", $value);
                                            $vendorAlias = mb_strtolower($vendorAlias, 'UTF-8');
                                            $vendorAlias = preg_replace('/[^a-zA-Zа-яА-Я]/ui', '', $vendorAlias);
                                            $vendorAlias = $this->TransUrl($vendorAlias);
                                            $vendor = $this->em
                                                ->getRepository('AppBundle:Vendor')
                                                ->findOneBy(array(
                                                    'alias' => $vendorAlias
                                                ));
                                            if (!$vendor) {
                                                $vendor = new Vendor();
                                                $vendor->setName($vendorName);
                                                $vendor->setAlias($vendorAlias);
                                                $this->em->persist($vendor);
                                                $this->em->flush($vendor);
                                            }
                                            $newGoods->setVendor($vendor);
                                            break;
                                    }
                                }
                            } while ($xmlReader->localName != 'offer');
                            if ($groupId) {
                                $newGoods->setGroupId($groupId);
                            }
                            $newGoods->setOffer($offer);
                            $newGoods->setAlias($this->TransUrl($aliasName));
                            $newGoods->setIsDelete(false);
                            $this->em->persist($newGoods);
                        }
                        $countIndex++;
                        if ($countIndex > 0 && $countIndex % 100000 == 0) {
                            $this->outputWriteLn('Обработано ' . $countIndex . ' товаров!');
                            $newGoods = null;
                            $oldGoods = null;
                            $this->em->flush();
                            $this->em->clear('AppBundle\Entity\Goods');
                            $this->em->clear('AppBundle\Entity\Vendor');
                        }
                    }
                }
            }
            $this->outputWriteLn('Всего обработано <red>' . $countIndex . '</red> товаров!');
        }

        $offer->setVersion($version);
        $this->em->persist($offer);
        $this->em->flush();

        $this->outputWriteLn('Конец парсинга оффера');

    }

    private function TransUrl($str)
    {
        $tr = array(
            "А"=>"a",
            "Б"=>"b",
            "В"=>"v",
            "Г"=>"g",
            "Д"=>"d",
            "Е"=>"e",
            "Ё"=>"e",
            "Ж"=>"j",
            "З"=>"z",
            "И"=>"i",
            "Й"=>"y",
            "К"=>"k",
            "Л"=>"l",
            "М"=>"m",
            "Н"=>"n",
            "О"=>"o",
            "П"=>"p",
            "Р"=>"r",
            "С"=>"s",
            "Т"=>"t",
            "У"=>"u",
            "Ф"=>"f",
            "Х"=>"h",
            "Ц"=>"ts",
            "Ч"=>"ch",
            "Ш"=>"sh",
            "Щ"=>"sch",
            "Ъ"=>"",
            "Ы"=>"i",
            "Ь"=>"j",
            "Э"=>"e",
            "Ю"=>"yu",
            "Я"=>"ya",
            "а"=>"a",
            "б"=>"b",
            "в"=>"v",
            "г"=>"g",
            "д"=>"d",
            "е"=>"e",
            "ё"=>"e",
            "ж"=>"j",
            "з"=>"z",
            "и"=>"i",
            "й"=>"y",
            "к"=>"k",
            "л"=>"l",
            "м"=>"m",
            "н"=>"n",
            "о"=>"o",
            "п"=>"p",
            "р"=>"r",
            "с"=>"s",
            "т"=>"t",
            "у"=>"u",
            "ф"=>"f",
            "х"=>"h",
            "ц"=>"ts",
            "ч"=>"ch",
            "ш"=>"sh",
            "щ"=>"sch",
            "ъ"=>"y",
            "ы"=>"i",
            "ь"=>"j",
            "э"=>"e",
            "ю"=>"yu",
            "я"=>"ya",
            " "=> "_",
            "."=> "",
            "/"=> "_",
            ","=>"_",
            "-"=>"_",
            "("=>"",
            ")"=>"",
            "["=>"",
            "]"=>"",
            "="=>"_",
            "+"=>"_",
            "*"=>"",
            "?"=>"",
            "\""=>"",
            "'"=>"",
            "&"=>"",
            "%"=>"",
            "#"=>"",
            "@"=>"",
            "!"=>"",
            ";"=>"",
            "№"=>"",
            "^"=>"",
            ":"=>"",
            "~"=>"",
            "\\"=>""
        );
        return strtr($str,$tr);
    }
}