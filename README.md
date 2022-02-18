# MPOSplitter

Convert a Fujifilm Finepix Real-3D MPO file to a stereo JPG

## Install

`composer global require betanerds/mposplitter`

Make sure the `~/.composer/vendor/bin` is in your `PATH`

## Usage

`mposplitter convert [options] [--] <filename>`

### Arguments

`filename`                   The MPO file to convert

### Options

`--quality[=QUALITY]`        Quality of the stereo JPG [default: 100]

`--focus|--no-focus`         Show focus helpers on the stereo JPG [default: focus]

`--focus-size[=FOCUS-SIZE]`  Size of the focus helpers on the stereo JPG [default: 50]

`--frames|--no-frames`       Show frames on the stereo JPG [default: frames]

`--frame-size[=FRAME-SIZE]`  Size of the frames around the stereo-pairs [default: 100]

`--text[=TEXT]`              Adds text under the right stereo-part

`--font-size[=TEXT]`         The fontsize for the text [default: 48]

`--font[=TEXT]`              The font to use (must be in the fonts folder) [default: "aAntiCorona.ttf"]

`--output[=OUTPUT]`          The output filename [default: `<filename>-stereo`.jpg]


# Disclaimer
The font 'A Anti Corona' is a free font by ''Wahyu Eka Prasetya' and downloaded from 'www.1001freefonts.com'