# MPOSplitter

Convert a Fujifilm Finepix Real-3D MPO file to a stereo JPG

## Install

```composer global require betanerds/mposplitter```

Make sure the ```~/.composer/vendor/bin``` is in your ```PATH```

## Usage

```mposplitter convert [options] [--] <filename>```

### Arguments

```filename```                       The MPO file to convert

### Options

```--quality[=QUALITY]```        Quality of the stereo JPG [default: 100]

```--focus|--no-focus```         Show focus helpers on the stereo JPG

```--focus-size[=FOCUS-SIZE] ``` Size of the focus helpers on the stereo JPG [default: 50]

```--frames|--no-frames ```      Show frames on the stereo JPG

```--frame-size[=FRAME-SIZE]```  Size of the focus helpers on the stereo JPG [default: 100]

```--output[=OUTPUT]   ```       The output filename
