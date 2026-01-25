#!/bin/bash
#
# Test Deployment Script
# Usage: bash test_deployment.sh [server_url]
#

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get server URL
SERVER_URL=${1:-http://localhost}

echo "======================================"
echo "Testing Supervisord Monitor Deployment"
echo "======================================"
echo ""
echo -e "${BLUE}Server URL: $SERVER_URL${NC}"
echo ""

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to test
test_endpoint() {
    local name=$1
    local url=$2
    local expected_code=$3
    
    echo -n "Testing $name... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null)
    
    if [ "$response" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (Expected: $expected_code, Got: $response)"
        ((TESTS_FAILED++))
        return 1
    fi
}

# Test 1: Main page
test_endpoint "Main Page" "$SERVER_URL" "200"

# Test 2: Index PHP
test_endpoint "Index PHP" "$SERVER_URL/index.php" "200"

# Test 3: CSS file
test_endpoint "CSS Bootstrap" "$SERVER_URL/css/bootstrap.min.css" "200"

# Test 4: JS file  
test_endpoint "JS Bootstrap" "$SERVER_URL/js/bootstrap.min.js" "200"

# Test 5: Custom CSS
test_endpoint "Custom CSS" "$SERVER_URL/css/custom.css" "200"

# Test 6: Application folder (should be blocked)
test_endpoint "Application Folder (should be 404)" "$SERVER_URL/application/" "404"

# Test 7: System folder (should be blocked)
test_endpoint "System Folder (should be 404)" "$SERVER_URL/system/" "404"

# Test 8: Check PHP-FPM
echo ""
echo -n "Testing PHP-FPM... "
php_test=$(curl -s "$SERVER_URL/index.php" | grep -o "<!DOCTYPE html" 2>/dev/null)
if [ ! -z "$php_test" ]; then
    echo -e "${GREEN}✓ PASS${NC} (PHP is working)"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ FAIL${NC} (PHP not processing)"
    ((TESTS_FAILED++))
fi

# Test 9: Check CodeIgniter
echo -n "Testing CodeIgniter... "
ci_test=$(curl -s "$SERVER_URL" | grep -o "Omisell Supervisord" 2>/dev/null)
if [ ! -z "$ci_test" ]; then
    echo -e "${GREEN}✓ PASS${NC} (CodeIgniter loaded)"
    ((TESTS_PASSED++))
else
    echo -e "${YELLOW}⚠ WARNING${NC} (May need login or config)"
    # Don't count as failure since it might be behind auth
fi

# Test 10: Response time
echo -n "Testing Response Time... "
start_time=$(date +%s%N)
curl -s "$SERVER_URL" > /dev/null 2>&1
end_time=$(date +%s%N)
response_time=$(( ($end_time - $start_time) / 1000000 ))

if [ $response_time -lt 2000 ]; then
    echo -e "${GREEN}✓ PASS${NC} (${response_time}ms - Excellent)"
    ((TESTS_PASSED++))
elif [ $response_time -lt 5000 ]; then
    echo -e "${YELLOW}⚠ WARNING${NC} (${response_time}ms - Acceptable)"
    ((TESTS_PASSED++))
else
    echo -e "${RED}✗ FAIL${NC} (${response_time}ms - Too slow)"
    ((TESTS_FAILED++))
fi

# Summary
echo ""
echo "======================================"
echo "Test Summary"
echo "======================================"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo ""
    echo "Your deployment is working correctly."
    echo "You can now access: $SERVER_URL"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    echo ""
    echo "Please check:"
    echo "1. Nginx error log: tail -f /var/log/nginx/supervisor-monitor-error.log"
    echo "2. PHP-FPM log: tail -f /var/log/php-fpm/error.log"
    echo "3. Nginx config: nginx -t"
    echo "4. File permissions in deployment directory"
    exit 1
fi
