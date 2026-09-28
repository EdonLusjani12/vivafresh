# Create a VERY simple test
cat > /home/vivafre1/public_html/simple_test.php << 'EOF'
<?php
echo "Step 1: Basic PHP works<br>";

// Test file existence
$env_file = '/home/secure/.env';
echo "Step 2: Checking $env_file<br>";

if (file_exists($env_file)) {
    echo "✅ File exists<br>";
    
    if (is_readable($env_file)) {
        echo "✅ File is readable<br>";
        
        // Try to read first line
        $handle = fopen($env_file, 'r');
        if ($handle) {
            $line = fgets($handle);
            echo "First line: " . htmlspecialchars($line) . "<br>";
            fclose($handle);
        } else {
            echo "❌ Cannot open file<br>";
        }
    } else {
        echo "❌ File exists but NOT readable<br>";
        echo "Permissions: " . substr(sprintf('%o', fileperms($env_file)), -4) . "<br>";
    }
} else {
    echo "❌ File does not exist<br>";
}

echo "<br>Step 3: Testing database...<br>";

// Test database connection
$conn = @mysqli_connect('136.243.110.241', 'vivafre1_sa', 'Ed23082003!@', 'vivafre1_Merki', 3306);
if ($conn) {
    echo "✅ Database connection successful!<br>";
    mysqli_close($conn);
} else {
    echo "❌ Database connection failed<br>";
}

echo "<br>✅ Test completed";
EOF