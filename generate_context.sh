#!/usr/bin/env bash

# Output context file name
OUTPUT_FILE="PROJECT_CONTEXT.md"

echo "=================================================="
echo " Generating $OUTPUT_FILE for Gemini Context..."
echo "=================================================="

# 1. Clear old context file
echo "# Laravel School ERP - Unified Architectural Context" > "$OUTPUT_FILE"
echo "*Generated on: $(date)*\n" >> "$OUTPUT_FILE"

# 2. Add Project Directory Tree
echo "## 1. Directory Structure" >> "$OUTPUT_FILE"
echo '```' >> "$OUTPUT_FILE"
if command -v tree &> /dev/null; then
    tree -I 'node_modules|vendor|storage|public|bootstrap/cache|.git' -L 3 >> "$OUTPUT_FILE"
else
    find . -maxdepth 3 -not -path '*/.*' -not -path './vendor*' -not -path './node_modules*' -not -path './storage*' >> "$OUTPUT_FILE"
fi
echo '```' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 3. Dump fresh Database Schema if artisan exists
echo "## 2. Database Schema (DDL Dump)" >> "$OUTPUT_FILE"
echo '```sql' >> "$OUTPUT_FILE"
if [ -f "database/schema/mysql-schema.dump" ]; then
    cat database/schema/mysql-schema.dump >> "$OUTPUT_FILE"
elif [ -f "database/schema/sqlite-schema.dump" ]; then
    cat database/schema/sqlite-schema.dump >> "$OUTPUT_FILE"
else
    echo "-- Running artisan schema:dump..."
    php artisan schema:dump --quiet
    cat database/schema/* >> "$OUTPUT_FILE" 2>/dev/null || echo "-- Schema dump unavailable"
fi
echo '```' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 4. Export API Routes
echo "## 3. Registered API Routes" >> "$OUTPUT_FILE"
echo '```' >> "$OUTPUT_FILE"
php artisan route:list --path=api >> "$OUTPUT_FILE" 2>/dev/null || echo "-- Could not generate route list"
echo '```' >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# 5. Bundle Core Models
echo "## 4. Eloquent Domain Models" >> "$OUTPUT_FILE"
if [ -d "app/Models" ]; then
    for file in app/Models/*.php; do
        if [ -f "$file" ]; then
            echo "### File: \`$file\`" >> "$OUTPUT_FILE"
            echo '```php' >> "$OUTPUT_FILE"
            cat "$file" >> "$OUTPUT_FILE"
            echo '```' >> "$OUTPUT_FILE"
            echo "" >> "$OUTPUT_FILE"
        fi
    done
fi

# 6. Bundle API V1 Controllers
echo "## 5. API V1 Controllers" >> "$OUTPUT_FILE"
if [ -d "app/Http/Controllers/Api/V1" ]; then
    for file in app/Http/Controllers/Api/V1/*.php; do
        if [ -f "$file" ]; then
            echo "### File: \`$file\`" >> "$OUTPUT_FILE"
            echo '```php' >> "$OUTPUT_FILE"
            cat "$file" >> "$OUTPUT_FILE"
            echo '```' >> "$OUTPUT_FILE"
            echo "" >> "$OUTPUT_FILE"
        fi
    done
fi

# 7. Bundle Core Scopes & Concerns
echo "## 6. Architectural Traits & Concerns" >> "$OUTPUT_FILE"
if [ -d "app/Models/Concerns" ]; then
    for file in app/Models/Concerns/*.php; do
        if [ -f "$file" ]; then
            echo "### File: \`$file\`" >> "$OUTPUT_FILE"
            echo '```php' >> "$OUTPUT_FILE"
            cat "$file" >> "$OUTPUT_FILE"
            echo '```' >> "$OUTPUT_FILE"
            echo "" >> "$OUTPUT_FILE"
        fi
    done
fi

echo "=================================================="
echo " Done! $OUTPUT_FILE has been generated."
echo " File Size: $(du -h $OUTPUT_FILE | cut -f1)"
echo "=================================================="
