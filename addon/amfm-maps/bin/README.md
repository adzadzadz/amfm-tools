# AMFM Maps - Binary Scripts Directory

This directory contains executable scripts for building, testing, and managing the AMFM Maps plugin.

## Scripts

### Testing Scripts
- **`verify-setup.sh`** - Verifies that the testing environment is properly configured
- **`run-tests.sh`** - Main test runner for Playwright tests with various options
- **`test-runner.sh`** - Alternative test runner with different test type options

### Build & Release Scripts  
- **`build.sh`** - Builds the plugin for distribution
- **`build.bat`** - Windows version of the build script
- **`release.sh`** - Handles plugin release process

## Usage

All scripts should be run from the plugin root directory:

```bash
# From plugin root directory
./bin/verify-setup.sh
./bin/run-tests.sh debug
./bin/build.sh
```

The scripts automatically detect their location and change to the plugin root directory before executing.

## Testing Workflow

1. **Setup**: `./bin/verify-setup.sh`
2. **Debug**: `./bin/run-tests.sh debug` 
3. **Full Tests**: `./bin/run-tests.sh`
4. **Specific Tests**: `./bin/run-tests.sh map|filter|integration`

See `../TESTING-SETUP.md` for detailed testing instructions.
