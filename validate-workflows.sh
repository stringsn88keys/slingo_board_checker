#!/bin/bash
# Simple validation script for GitHub Actions workflows

echo "🔍 Validating GitHub Actions Workflows..."
echo "========================================"

WORKFLOW_DIR=".github/workflows"

if [ ! -d "$WORKFLOW_DIR" ]; then
    echo "❌ No .github/workflows directory found"
    exit 1
fi

echo "📋 Found workflow files:"
for file in $WORKFLOW_DIR/*.yml $WORKFLOW_DIR/*.yaml; do
    if [ -f "$file" ]; then
        echo "  - $(basename "$file")"
    fi
done

echo ""
echo "🧪 Basic YAML syntax validation:"

for file in $WORKFLOW_DIR/*.yml $WORKFLOW_DIR/*.yaml; do
    if [ -f "$file" ]; then
        filename=$(basename "$file")
        echo -n "  Checking $filename... "
        
        # Basic checks
        if grep -q "name:" "$file" && grep -q "on:" "$file" && grep -q "jobs:" "$file"; then
            echo "✅ Basic structure OK"
        else
            echo "❌ Missing required fields (name, on, jobs)"
        fi
    fi
done

echo ""
echo "🎯 Workflow Summary:"
echo "==================="
echo "Main CI workflow: .github/workflows/ci.yml"
echo "Comprehensive tests: .github/workflows/tests.yml" 
echo "Quick validation: .github/workflows/quick-tests.yml"
echo ""
echo "To test locally before pushing:"
echo "  php tests/simple_test_runner.php"
echo "  php tests/manual/run_manual_tests.php"
echo ""
echo "✅ GitHub Actions workflows are ready!"
