#!/bin/bash

# AMFM Maps Testing - Environment Verification
echo "🔍 AMFM Maps Testing Environment Verification"
echo "============================================="

# Change to plugin root directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PLUGIN_ROOT"

echo "Working directory: $(pwd)"
echo ""

# Check WordPress connection
echo "1. Checking WordPress connection..."
if curl -s --head "http://localhost:10003" | head -n 1 | grep -q "200 OK"; then
    echo "   ✅ WordPress is accessible at http://localhost:10003"
else
    echo "   ❌ Cannot connect to WordPress at http://localhost:10003"
    echo "   Please ensure your WordPress development environment is running"
    exit 1
fi

# Check WordPress admin login
echo "2. Checking WordPress admin access..."
LOGIN_RESPONSE=$(curl -s -d "log=adrian.saycon@amfmhealthcare.com&pwd=password" -X POST "http://localhost:10003/wp-login.php")
if echo "$LOGIN_RESPONSE" | grep -q "dashboard" || echo "$LOGIN_RESPONSE" | grep -q "wp-admin"; then
    echo "   ✅ Admin login credentials are working"
else
    echo "   ❌ Admin login failed - please check credentials"
    echo "   Expected: adrian.saycon@amfmhealthcare.com / password"
fi

# Check Node.js
echo "3. Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "   ✅ Node.js is installed: $NODE_VERSION"
else
    echo "   ❌ Node.js is not installed"
    exit 1
fi

# Check if dependencies are installed
echo "4. Checking npm dependencies..."
if [ -f "package.json" ] && [ -d "node_modules" ]; then
    echo "   ✅ npm dependencies are installed"
else
    echo "   ⚠️  npm dependencies not found, installing..."
    npm install
fi

# Check Playwright
echo "5. Checking Playwright..."
if npx playwright --version &> /dev/null; then
    PLAYWRIGHT_VERSION=$(npx playwright --version)
    echo "   ✅ Playwright is installed: $PLAYWRIGHT_VERSION"
else
    echo "   ❌ Playwright is not installed"
    exit 1
fi

# Check if browsers are installed
echo "6. Checking Playwright browsers..."
if npx playwright install --dry-run chromium 2>&1 | grep -q "is already installed"; then
    echo "   ✅ Chromium browser is installed"
else
    echo "   ⚠️  Installing Chromium browser..."
    npx playwright install chromium
fi

echo ""
echo "🎉 Environment verification complete!"
echo ""
echo "Next steps:"
echo "1. Run debugging test: ./bin/run-tests.sh debug"
echo "2. Run all tests: ./bin/run-tests.sh"
echo "3. View test results: npx playwright show-report"
