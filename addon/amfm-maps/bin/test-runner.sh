#!/bin/bash

# AMFM Maps Plugin Test Runner
# This script helps you run the Playwright tests for the AMFM Maps plugin

echo "🚀 AMFM Maps Plugin Test Runner"
echo "================================="

# Change to plugin root directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PLUGIN_ROOT"

echo "Working directory: $(pwd)"
echo ""

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    echo "❌ Error: Please run this script from the plugin root directory (where package.json is located)"
    exit 1
fi

# Check if dependencies are installed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

# Check if Playwright browsers are installed
if [ ! -d "node_modules/@playwright/test" ]; then
    echo "🎭 Installing Playwright browsers..."
    npx playwright install
fi

# Function to run specific test types
run_test() {
    local test_type=$1
    echo "🧪 Running $test_type tests..."
    
    case $test_type in
        "map")
            npx playwright test map-widget.spec.ts --headed
            ;;
        "filter")
            npx playwright test filter-widget.spec.ts --headed
            ;;
        "integration")
            npx playwright test integration.spec.ts --headed
            ;;
        "debug")
            npx playwright test debug-filtering.spec.ts --headed
            ;;
        "all")
            npx playwright test --headed
            ;;
        *)
            echo "❌ Unknown test type: $test_type"
            echo "Available options: map, filter, integration, debug, all"
            exit 1
            ;;
    esac
}

# Main menu
echo ""
echo "What would you like to do?"
echo "1. Run all tests"
echo "2. Run map widget tests only"
echo "3. Run filter widget tests only"
echo "4. Run integration tests only"
echo "5. Run debug tests (for filtering issues)"
echo "6. Open Playwright UI mode"
echo "7. Generate test code"
echo "8. View test report"
echo ""

read -p "Enter your choice (1-8): " choice

case $choice in
    1)
        run_test "all"
        ;;
    2)
        run_test "map"
        ;;
    3)
        run_test "filter"
        ;;
    4)
        run_test "integration"
        ;;
    5)
        run_test "debug"
        ;;
    6)
        echo "🎭 Opening Playwright UI mode..."
        npx playwright test --ui
        ;;
    7)
        echo "🔧 Generating test code..."
        read -p "Enter the URL to generate tests for: " url
        npx playwright codegen "$url"
        ;;
    8)
        echo "📊 Opening test report..."
        npx playwright show-report
        ;;
    *)
        echo "❌ Invalid choice. Please enter 1-8."
        exit 1
        ;;
esac

echo ""
echo "✅ Done! Check the test results above."
echo ""
echo "💡 Tips:"
echo "   - Test screenshots are saved in test-results/"
echo "   - Use 'npm run test:debug' for debugging mode"
echo "   - Use 'npm run test:ui' for interactive UI mode"
echo "   - Check tests/README.md for detailed documentation"
