<?php

namespace Betanerds\Mposplitter\Command;

use lsolesen\pel\PelException;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelInvalidArgumentException;
use lsolesen\pel\PelInvalidDataException;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'convert',
    description: 'Convert a Fujifilm Finepix Real 3D MPO file to a stereo SBS (Side By Side) JPG',
)]
class CreateStereoviewerJpegCommand extends Command
{
    const LEFT_IMAGE = 0;
    const RIGHT_IMAGE = 1;

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'The MPO file to convert');
        $this->addOption('focus', null, InputOption::VALUE_NEGATABLE, 'Show focus helpers on the stereo JPG', true);
        $this->addOption('exif', null, InputOption::VALUE_NEGATABLE, 'Preserve Exif data', false);
        $this->addOption('focus-size', null, InputOption::VALUE_OPTIONAL, 'Size of the focus helpers on the stereo JPG', 50);
        $this->addOption('frames', null, InputOption::VALUE_NEGATABLE, 'Show frames on the stereo JPG', true);
        $this->addOption('frame-size', null, InputOption::VALUE_OPTIONAL, 'Size of the frames around the stereo-pairs', 100);
        $this->addOption('text', null, InputOption::VALUE_OPTIONAL, 'Adds text under the stereo JPG');
        $this->addOption('text-position', null, InputOption::VALUE_OPTIONAL, 'Position where to put the text', 'R');
        $this->addOption('font-size', null, InputOption::VALUE_OPTIONAL, 'The fontsize for the text', 48);
        $this->addOption('font', null, InputOption::VALUE_OPTIONAL, 'The font to use (must be in the fonts folder)', 'aAntiCorona.ttf');
        $this->addOption('output', null, InputOption::VALUE_OPTIONAL, 'The output filename');
    }

    /**
     * @throws PelInvalidArgumentException
     * @throws PelInvalidDataException
     * @throws PelException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');
        $path_parts = pathinfo($filename);

        if (!file_exists($filename)) {
            $output->writeln(sprintf('File (%s) not found', $filename));

            return Command::FAILURE;
        }

        $buffer = file_get_contents($filename);

        $token = pack('CCCC', 0xff, 0xd8, 0xff, 0xe1);
        $split = preg_split("/$token/", $buffer, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        unset($buffer); // freemem

        if (2 !== count($split)) {
            $output->writeln(sprintf('Incorrect number (%d) of JPG files in the MPO file', count($split)));

            return Command::FAILURE;
        }

        $images = [];
        foreach ($split as $value) {
            $images[] = imagecreatefromstring($token . $value);
        }

        unset($value); // freemem
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

        // Write text under the right image
        $text = $input->getOption('text');
        $font_size = $input->getOption('font-size');
        $font_ttf = $input->getOption('font');
        $text_position = $input->getOption('text-position');

        if ($text && $font_size && $stereoFrameWidth && $font_ttf) {
            $font = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $font_ttf;

            if (!file_exists($font)) {
                $output->writeln(sprintf('Font (%s) not found', $font_ttf));

                return Command::FAILURE;
            }

            $image_text = imagecreatetruecolor($rightWidth, $stereoFrameWidth);

            $text_color = imagecolorallocate($image_text, 255, 255, 255);

            $bbox = imagettfbbox($font_size, 0, $font, $text);
            $x = (int)($bbox[0] + (imagesx($image_text) / 2) - ($bbox[4] / 2) - 25);
            $y = (int)($bbox[1] + (imagesy($image_text) / 2) - ($bbox[5] / 2) - 5);

            imagettftext($image_text, $font_size, 0, $x, $y, $text_color, $font, $text);

            if ('R' === $text_position || 'B' === $text_position) {
                imagecopymerge($stereoImage, $image_text, $leftWidth + (2 * $stereoFrameWidth), $stereoFrameWidth + $rightHeight, 0, 0, $rightWidth, $stereoFrameWidth, 100);
            }
            if ('L' === $text_position || 'B' === $text_position) {
                imagecopymerge($stereoImage, $image_text, $stereoFrameWidth, $stereoFrameWidth + $leftHeight, 0, 0, $leftWidth, $stereoFrameWidth, 100);
            }

            imagedestroy($image_text); // freemem
        }

        unset($images); // freemem

        // Preserve exif information
        $output_jpeg = new PelJpeg($stereoImage);

        $pixelXDimension = imagesx($stereoImage);
        $pixelYDimension = imagesy($stereoImage);

        imagedestroy($stereoImage); // freemem

        if ($input->getOption('exif')) {
            $input_jpeg = new PelJpeg($filename);
            $exif = $input_jpeg->getExif();
            if ($exif instanceof PelExif) {

                $tiff = $exif->getTiff();
                $ifId0 = $tiff->getIfd();

                // Fix widht / heigth
                $ifId0->getSubIfd(PelTag::INTEROPERABILITY_VERSION)->getEntry(PelTag::PIXEL_X_DIMENSION)->setValue($pixelXDimension);
                $ifId0->getSubIfd(PelTag::INTEROPERABILITY_VERSION)->getEntry(PelTag::PIXEL_Y_DIMENSION)->setValue($pixelYDimension);

                // Replace 'Software' tag
                $ifId0->getEntry(PelTag::SOFTWARE)->setValue('BetaNerds - mposplitter v1.0');

                $output_jpeg->setExif($exif);
            }
        }

        // Save the stereo image
        $outfile = $input->getOption('output') ?? sprintf('%s-%s.jpg', $path_parts['filename'], 'stereo');
        $output_jpeg->saveFile($outfile);

        $output->writeln(sprintf('Converted stereo JPG: %s', $outfile));

        return Command::SUCCESS;
    }
}
