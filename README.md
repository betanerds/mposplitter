# MPOSplitter

Convert a Fujifilm Finepix Real-3D MPO file to a stereo SBS (Side By Side) JPG

## Install

`composer global require betanerds/mposplitter`

Make sure the `~/.composer/vendor/bin` is in your `PATH`

## Usage

`mposplitter convert [options] [--] <filename>`

### Arguments

`filename`                   The MPO file to convert

### Options

`--no-focus`                Do not show focus helpers on the stereo JPG

`--no-exif`                 Do not preserve Exif data

`--cross`                   Use cross-eyed view (swap left and right)

`--focus-size[=FOCUS-SIZE]` Size of the focus helpers on the stereo JPG [default: 50]

`--no-frames`               Do not show frames on the stereo JPG

`--frame-size[=FRAME-SIZE]` Size of the frames around the stereo-pairs [default: 100]

`--text[=TEXT]`             Adds text under the stereo JPG

`--text-position[=L|R|B]`   Position where to put the text, [L] Left, [R] Right, [B] Both [default:R]

`--font-size[=TEXT]`        The fontsize for the text [default: 48]

`--font[=TEXT]`             The font to use (must be in the 'fonts' folder) [default: "aAntiCorona.ttf"]

`--resize`                  Resize the images percentage [default: 100]

`--output[=OUTPUT]`         The output filename [default: `<filename>-stereo`.jpg]

# About
For more information about the background and the other parts of this project, go to www.stereoscope.nl

# Disclaimer
The font 'A Anti Corona' is a free font by 'Wahyu Eka Prasetya' and downloaded from www.1001freefonts.com

# Credits
Exif support by using package 'fileeye/pel'