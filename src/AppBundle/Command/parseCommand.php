<?php

namespace AppBundle\Command;

use AppBundle\Entity\Goods;
use AppBundle\Entity\Offer;
use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class parseCommand extends ContainerAwareCommand
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
            ->setName('main:parse')
            ->setDescription('Parse offers')
            ->addArgument(
                'offerId',
                InputArgument::OPTIONAL,
                'Who do you want to parse?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$em = $this->getContainer()->get('doctrine')->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        self::$output = $output;
        self::$tmpFilePath = '/tmp/tmpFile.xml';
        $offerId = intval($input->getArgument('offerId'));
        $offersArgs = ['isDelete' => 0];
        if ($offerId) {
            $offersArgs['id'] = $offerId;
        }
        $offers = self::$em
            ->getRepository('AppBundle:Offer')
            ->findBy($offersArgs);
        foreach ($offers as $offer) {
            self::$goodsGroupIds = [];
            $this->parseOffer($offer);
        }

        /** Проставляем isDelete для товаров, у оффера которых isDelete = 1 */
        $offers = self::$em
            ->getRepository('AppBundle:Offer')
            ->findBy([
                'isDelete' => 1
            ]);
        foreach ($offers as $offer) {
            $this->outputWriteLn('Логическое удаление товаров оффера ' . $offer->getName());
            $this->deleteGoodsByOffer($offer);
            $this->outputWriteLn('Логическое удаление товаров оффера ' . $offer->getName() . ' завершено');
        }
        $this->outputWriteLn('Физическое удаление товаров без бренда');
        $this->deleteGoodsByNoVendor();
        $this->outputWriteLn('Физическое удаление товаров без бренда завершено');
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
        $newTimeDate = $newTimeDate->format('Y-m-d H:i:s');
        self::$output->writeln(self::$delimer . ' ' . $newTimeDate . ' | <blue>' . $text . '</blue> | Memory usage: <red>' . round(memory_get_usage() / (1024 * 1024)) . ' MB</red>' . self::$delimer);
    }

    /**
     * @param $offer Offer
     */
    private function parseOffer($offer)
    {
        $version = $offer->getVersion();
        $version++;
        $this->outputWriteLn('--- Начало парсинга оффера <yellow>' . $offer->getName() . '</yellow> ---');
        $offerXmlUrl = $offer->getXmlParseUrl();

        $targetFile = fopen(self::$tmpFilePath, 'w');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $offerXmlUrl );
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, 'progressCallback']);
        curl_setopt($ch, CURLOPT_FILE, $targetFile);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
        fclose($targetFile);
        $fileSize = round(filesize(self::$tmpFilePath) / (1024 * 1024), 1);
        $this->outputWriteLn('Файл скачан | Размер: ' . $fileSize . ' MB');
        $xmlContent = null;
        $xmlReader = \XMLReader::open(self::$tmpFilePath);
        $countIndex = 0;
        $checked = true;
        $this->outputWriteLn('Проверка целостности XML');
        try {
            while ($xmlReader->read()) {
            }
        } catch (\Exception $e) {
            $checked = false;
            $this->outputWriteLn('Ошибка: ' . $e->getMessage());
        }
        $this->outputWriteLn('Проверка целостности XML Завершена');
        if ($checked) {
            $xmlReader = \XMLReader::open(self::$tmpFilePath);
            while ($xmlReader->read()) {
                if ($xmlReader->nodeType == \XMLReader::ELEMENT) {
                    if ($xmlReader->localName == 'offer') {
                        $groupId = $xmlReader->getAttribute('group_id');
                        self::$externalId = $xmlReader->getAttribute('id');
                        self::$goods = null;
                        self::$pictures = [];
                        self::$paramsArray = [];
                        self::$paramName = '';
                        if ($groupId) {
                            if (isset(self::$goodsGroupIds[$offer->getId()][$groupId])) {
                                continue;
                            }
                            self::$goods = self::$em
                                ->getRepository('AppBundle:Goods')
                                ->findOneBy([
                                    'groupId' => $groupId,
                                    'Offer' => $offer,
                                ]);
                            self::$goodsGroupIds[$offer->getId()][$groupId] = $groupId;
                        }
                        if (!self::$goods) {
                            self::$goods = self::$em
                                ->getRepository('AppBundle:Goods')
                                ->findOneBy([
                                    'externalId' => self::$externalId,
                                    'Offer' => $offer,
                                ]);
                        }
                        if (self::$goods) {
                            self::$goodsType = 'update';
                        } else {
                            self::$goodsType = 'insert';
                            self::$aliasName = '';
                            self::$goods = new Goods();
                            self::$goods->setExternalId(self::$externalId);
                            self::$goods->setOffer($offer);
                            if ($groupId) {
                                self::$goods->setGroupId($groupId);
                            }
                        }
                        do {
                            $xmlReader->read();
                            if ($xmlReader->nodeType != \XMLReader::END_ELEMENT) {
                                $tagName = $xmlReader->localName;
                                if ($tagName == 'param') {
                                    self::$paramName = $xmlReader->getAttribute('name');
                                }
                                $xmlReader->read();
                                $value = $xmlReader->value;
                                $this->checkXmlItem($tagName, $value);
                            }
                        } while ($xmlReader->localName != 'offer');
                        if (self::$pictures) {
                            self::$goods->setPictures(self::$pictures);
                        }
                        if (self::$paramsArray) {
                            self::$goods->setParams(self::$paramsArray);
                        }
                        self::$goods->setIsDelete(false);
                        self::$goods->setVersion($version);
                        self::$em->persist(self::$goods);
                        $countIndex++;

                        if ($countIndex > 0 && $countIndex % 10000 == 0) {
                            if ($countIndex % 50000 == 0) {
                                $this->outputWriteLn('Обработано <red>' . $countIndex . '</red> товаров!');
                            }
                            self::$goods = null;
                            self::$em->flush();
                            self::$em->clear('AppBundle\Entity\Goods');
                            self::$em->clear('AppBundle\Entity\Vendor');
                        }
                    }
                }
            }
            $this->outputWriteLn('Всего обработано <red>' . $countIndex . '</red> товаров!');
        }

        $offer->setVersion($version);
        self::$em->persist($offer);
        self::$em->flush();
        unlink(self::$tmpFilePath);

        $this->outputWriteLn('Конец парсинга оффера ' . $offer->getName());

    }

    /**
     * @param $tagName string
     * @param $value string
     */
    private function checkXmlItem($tagName, $value)
    {
        switch ($tagName) {
            case 'currencyId':
                if (self::$goodsType == 'insert') {
                    self::$goods->setCurrency($value);
                }
                break;
            case 'description':
                if (self::$goodsType == 'insert') {
                    self::$goods->setDescription($value);
                }
                break;
            case 'market_category':
                if (self::$goodsType == 'insert') {
                    self::$goods->setCategory($value);
                }
                break;
            case 'model':
                if (self::$goodsType == 'insert') {
                    self::$goods->setModel($value);
                }
                break;
            case 'name':
                if (self::$goodsType == 'insert') {
                    $value = mb_substr($value, 0, 50, 'UTF-8');
                    self::$aliasName = self::$externalId . '_' . $value;
                    self::$goods->setName($value);
                }
                break;
            case 'price':
                self::$goods->setPrice(floatval(str_replace(' ', '', $value)));
                break;
            case 'oldprice':
                self::$goods->setOldPrice(floatval(str_replace(' ', '', $value)));
                break;
            case 'url':
                self::$goods->setUrl($value);
                break;
            case 'vendorCode':
                if (self::$goodsType == 'insert') {
                    self::$goods->setVendorCode($value);
                }
                break;
            case 'vendor':
                if (self::$goodsType == 'insert' || (self::$goodsType == 'update' && !self::$goods->getVendor() && $value)) {
                    self::setVendor($value);
                }
                break;
            case 'picture':
                if (self::$goodsType == 'insert' || (self::$goodsType == 'update' && !self::$goods->getPictures() && $value)) {
                    self::$pictures[] = $value;
                }
                break;
            case 'param':
                if (self::$goodsType == 'insert' && self::$paramName && $value) {
                    self::$paramsArray[self::$paramName] = $value;
                }
                break;
        }
        if (self::$goodsType == 'insert') {
            self::$goods->setAlias(self::TransUrl(self::$aliasName));
        }
    }

    /**
     * @param $value string
     */
    private static function setVendor($value)
    {
        $vendorAlias = $vendorName = iconv("UTF-8", "UTF-8//IGNORE", $value);
        $vendorAlias = mb_strtolower($vendorAlias, 'UTF-8');
        $vendorAlias = preg_replace('/[^a-zA-Zа-яА-Я0-9]/ui', '', $vendorAlias);
        $vendorAlias = self::TransUrl($vendorAlias);
        $vendor = self::$em
            ->getRepository('AppBundle:Vendor')
            ->findOneBy(['alias' => $vendorAlias]);
        if (!$vendor) {
            $vendor = new Vendor();
            $vendor->setName($vendorName);
            $vendor->setAlias($vendorAlias);
            self::$em->persist($vendor);
            self::$em->flush($vendor);
        }
        self::$goods->setVendor($vendor);
    }

    /**
     * @param $str string
     * @return string
     */
    private static function TransUrl($str)
    {
        $tr = [
            "А" => "a",
            "Б" => "b",
            "В" => "v",
            "Г" => "g",
            "Д" => "d",
            "Е" => "e",
            "Ё" => "e",
            "Ж" => "j",
            "З" => "z",
            "И" => "i",
            "Й" => "y",
            "К" => "k",
            "Л" => "l",
            "М" => "m",
            "Н" => "n",
            "О" => "o",
            "П" => "p",
            "Р" => "r",
            "С" => "s",
            "Т" => "t",
            "У" => "u",
            "Ф" => "f",
            "Х" => "h",
            "Ц" => "ts",
            "Ч" => "ch",
            "Ш" => "sh",
            "Щ" => "sch",
            "Ъ" => "",
            "Ы" => "i",
            "Ь" => "j",
            "Э" => "e",
            "Ю" => "yu",
            "Я" => "ya",
            "а" => "a",
            "б" => "b",
            "в" => "v",
            "г" => "g",
            "д" => "d",
            "е" => "e",
            "ё" => "e",
            "ж" => "j",
            "з" => "z",
            "и" => "i",
            "й" => "y",
            "к" => "k",
            "л" => "l",
            "м" => "m",
            "н" => "n",
            "о" => "o",
            "п" => "p",
            "р" => "r",
            "с" => "s",
            "т" => "t",
            "у" => "u",
            "ф" => "f",
            "х" => "h",
            "ц" => "ts",
            "ч" => "ch",
            "ш" => "sh",
            "щ" => "sch",
            "ъ" => "y",
            "ы" => "i",
            "ь" => "j",
            "э" => "e",
            "ю" => "yu",
            "я" => "ya",
            " " => "_",
            "." => "",
            "/" => "_",
            "," => "_",
            "-" => "_",
            "(" => "",
            ")" => "",
            "[" => "",
            "]" => "",
            "=" => "_",
            "+" => "_",
            "*" => "",
            "?" => "",
            "\"" => "",
            "'" => "",
            "&" => "",
            "%" => "",
            "#" => "",
            "@" => "",
            "!" => "",
            ";" => "",
            "№" => "",
            "^" => "",
            ":" => "",
            "~" => "",
            "\\" => ""
        ];
        return strtr($str, $tr);
    }

    /**
     * @param $offer Offer
     */
    private function deleteGoodsByOffer($offer)
    {
        $connection = self::$em->getConnection();
        $statement = $connection->prepare("UPDATE goods SET isDelete = 1 WHERE isDelete = 0 AND offerId = :offerId");
        $statement->bindValue('offerId', $offer->getId());
        $statement->execute();
    }

    /**
     * Удаление товаров без бренда
     */
    private function deleteGoodsByNoVendor()
    {
        $connection = self::$em->getConnection();
        $statement = $connection->prepare("DELETE FROM goods WHERE vendorId IS NULL");
        $statement->execute();
    }

    function progressCallback($resource, $download_size, $downloaded_size, $upload_size, $uploaded_size)
    {
        static $previousProgress = 0;

        if ( $download_size == 0 ) {
            $progress = 0;
        } else {
            $progress = round( $downloaded_size * 100 / $download_size );
        }
        if ( $progress > $previousProgress) {
            $fileSize = round($downloaded_size / (1024 * 1024), 1);
            $newTimeDate = new \DateTime();
            $newTimeDate = $newTimeDate->format('Y-m-d H:i:s');
            printf("\r" . self::$delimer . ' ' . $newTimeDate . ' | --- Скачивание файла : ' . $fileSize . ' MB --- |' . ' Memory usage: ' . round(memory_get_usage() / (1024 * 1024)) . ' MB' .  self::$delimer);
        }

    }
}