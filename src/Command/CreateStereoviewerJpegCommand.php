<?php

namespace Betanerds\Mposplitter\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateStereoviewerJpegCommand extends Command
{
    const LEFT_IMAGE = 0;
    const RIGHT_IMAGE = 1;

    protected static $defaultName = 'convert';
    protected static $defaultDescription = 'Convert a Fuji film Fine-pix Real 3D MPO file to a stereo JPG';

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'The MPO file to convert');
        $this->addOption('quality', null, InputOption::VALUE_OPTIONAL, 'Quality of the stereo JPG', 100);
        $this->addOption('focus', null, InputOption::VALUE_NEGATABLE, 'Show focus helpers on the stereo JPG', true);
        $this->addOption('focus-size', null, InputOption::VALUE_OPTIONAL, 'Size of the focus helpers on the stereo JPG', 50);
        $this->addOption('frames', null, InputOption::VALUE_NEGATABLE, 'Show frames on the stereo JPG', true);
        $this->addOption('frame-size', null, InputOption::VALUE_OPTIONAL, 'Size of the frames around the stereo-pairs', 100);
        $this->addOption('output', null, InputOption::VALUE_OPTIONAL, 'The output filename');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');
        $path_parts = pathinfo($filename);

        $buffer = file_get_contents($filename);

        $token = chr(0xff) . chr(0xd8) . chr(0xff) . chr(0xe1);
        $split = preg_split("/$token/", $buffer, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        unset($buffer); // freemem

        if (count($split) !== 2) {
            $output->writeln('Incorrect number of JPG files in the MPO file');

            return Command::FAILURE;
        }

        $images = [];
        foreach ($split as $value) {
            $images[] = imagecreatefromstring($token . $value);
        }

        unset($split); // freemem

        $leftWidth = imageSX($images[self::LEFT_IMAGE]);
        $leftHeight = imageSY($images[self::LEFT_IMAGE]);

        $rightWidth = imageSX($images[self::RIGHT_IMAGE]);
        $rightHeight = imageSY($images[self::RIGHT_IMAGE]);

        // Create the stereo canvas
        $stereoFrameWidth = $input->getOption('frames') ? $input->getOption('frame-size') : 0;
        $stereoImage = imagecreatetruecolor($leftWidth + $rightWidth + (3 * $stereoFrameWidth), $leftHeight + (2 * $stereoFrameWidth));

        // Create the focusing helpers
        $arcWidth = $input->getOption('focus-size');
        if ($input->getOption('focus') && $stereoFrameWidth >= $arcWidth) {
            $arc = imagecreatetruecolor($arcWidth, $arcWidth);
            $col_ellipse = imagecolorallocate($arc, 255, 255, 255);
            imagefilledellipse($arc, $arcWidth / 2, $arcWidth / 2, $arcWidth, $arcWidth, $col_ellipse);

            imagecopymerge($stereoImage, $arc, $stereoFrameWidth + ($leftWidth / 2) - ($arcWidth / 2), ($arcWidth / 2), 0, 0, $arcWidth, $arcWidth, 100);
            imagecopymerge($stereoImage, $arc, $stereoFrameWidth + $leftWidth + $stereoFrameWidth + ($rightWidth / 2) - ($arcWidth / 2), ($arcWidth / 2), 0, 0, $arcWidth, $arcWidth, 100);

            imagedestroy($arc); // freemem
        }

        // Merge the left and right images into the canvas
        imagecopymerge($stereoImage, $images[0], $stereoFrameWidth, $stereoFrameWidth, 0, 0, $leftWidth + $stereoFrameWidth, $leftHeight + $stereoFrameWidth, 100);
        imagecopymerge($stereoImage, $images[1], $leftWidth + (2 * $stereoFrameWidth), $stereoFrameWidth, 0, 0, $rightWidth, $rightHeight, 100);

        unset($images); // freemem

        // Save the stereo image
        $outfile = $input->getOption('output') ?? sprintf('%s/%s-%s.jpg', $path_parts['dirname'], $path_parts['filename'], 'stereo');
        imagejpeg($stereoImage, $outfile, $input->getOption('quality'));

        imagedestroy($stereoImage); // freemem

        $output->writeln(sprintf('Converted stereo JPG: %s', $outfile));

        return Command::SUCCESS;
    }
}
