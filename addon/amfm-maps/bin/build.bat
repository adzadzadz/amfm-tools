@echo off
setlocal

REM Check if a filename is provided
if "%~1"=="" (
    echo Usage: build.bat <filename>
    exit /b 1
)

REM Set the filename and build directory
set "FILENAME=%~1"
set "BUILD_DIR=build"

REM Create the build directory if it doesn't exist
if not exist "%BUILD_DIR%" (
    mkdir "%BUILD_DIR%"
)

REM Archive the current directory using Git
git archive --format=zip -o "%BUILD_DIR%\%FILENAME%.zip" HEAD

echo Archive created at %BUILD_DIR%\%FILENAME%.zip

endlocal