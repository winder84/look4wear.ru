<?php

namespace AppBundle\Command;

use AppBundle\Entity\Category;
use AppBundle\Entity\Vendor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class getBrandImagesCommand extends ContainerAwareCommand
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

    protected static $ctx;

    /**
     * @var string
     */
    protected static $uploadPath;

    protected function configure()
    {
        $this
            ->setName('main:getBrandImages')
            ->setDescription('Get Brand images')
            ->addArgument(
                'vendorAlias',
                InputArgument::OPTIONAL,
                'What brand?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$em = $this->getContainer()->get('doctrine')->getManager();
        self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        self::$output = $output;
        $this->outputWriteLn('Получаем изображения для брендов.');
        $vendorAlias = $input->getArgument('vendorAlias');
        self::$uploadPath = self::getContainer()->get('kernel')->getRootDir() . '/../web/media/brands/';
        if (!is_dir(self::$uploadPath)) {
            mkdir(self::$uploadPath);
        }
        if ($vendorAlias) {
            /** @var Category $category */
            $vendor = self::$em
                ->getRepository('AppBundle:Vendor')
                ->findOneBy([
                    'alias' => $vendorAlias
                ]);
            $vendors = [$vendor];
        } else {
            $vendors = self::$em
                ->getRepository('AppBundle:Vendor')
                ->findAll();
        }
        foreach ($vendors as $vendor) {
            self::getVendorImage($vendor);
        }
        $this->outputWriteLn('Получены изображения для брендов.');
    }

    /**
     * @param Vendor $vendor
     */
    private static function getVendorImage($vendor)
    {
        $kvAlias = str_replace(' ', '-', mb_strtolower($vendor->getName(), 'utf-8'));
        if (!file_exists(self::$uploadPath . $vendor->getAlias() . '.png')) {
            $kvUrl = 'https://' . $kvAlias;
            try {
                file_put_contents(self::$uploadPath . $vendor->getAlias() . '.png', file_get_contents($kvUrl . '.png'));
            } catch (\Exception $e) {
                echo $e->getMessage() . "\r\n";
            }
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
        $newTimeDate = $newTimeDate->format('Y-m-d H:i:s');
        self::$output->writeln(self::$delimer . ' ' . $newTimeDate . ' | <blue>' . $text . '</blue> | Memory usage: <red>' . round(memory_get_usage() / (1024 * 1024)) . ' MB</red>' . self::$delimer);
    }
}