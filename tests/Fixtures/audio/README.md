# Audio Test Fixtures

This directory contains audio files used for functional testing of the AudioResource integration with EdenAI API.

## Fixture Files

### valid-speech.mp3
- **Size**: ~5.6KB
- **Format**: MP3 (MPEG ADTS, layer III, v2, 40 kbps, 16 kHz, Monaural)
- **Duration**: ~1.23 seconds
- **Content**: Speech saying "Hello world, this is a test"
- **Source**: Generated using macOS `say` command and converted with ffmpeg
- **Purpose**: Valid audio file for successful speech-to-text transcription tests

### malformed.mp3
- **Size**: ~399 bytes
- **Format**: ASCII text (intentionally not a valid audio file)
- **Content**: Random text data simulating corrupted audio
- **Purpose**: Error testing - triggers validation exceptions when uploaded to audio APIs

## Usage

These fixtures are used in `/tests/Feature/Sandbox/AudioResourceSandboxTest.php` for testing:
- Success scenarios with valid audio input
- Error handling with malformed/invalid audio files

## Notes

- Fixtures are committed to the repository for consistent test execution
- Files are kept minimal (<100KB) to reduce API processing costs
- The valid speech file contains clear speech for reliable transcription testing
