<!DOCTYPE html>
<html>
<head>
    <title>API Test - Dentist Dashboard</title>
</head>
<body>
    <h1>Dentist Dashboard API Test</h1>
    <div id="results"></div>

    <script>
        // Test the API
        async function testAPI() {
            try {
                const response = await fetch('../api/dentist.php?action=getComprehensiveAnalytics');
                const data = await response.json();
                
                document.getElementById('results').innerHTML = `
                    <h2>API Response:</h2>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                    <p>Status: ${response.status}</p>
                `;
            } catch (error) {
                document.getElementById('results').innerHTML = `
                    <h2>Error:</h2>
                    <p>${error.message}</p>
                `;
            }
        }

        // Test when page loads
        window.onload = testAPI;
    </script>
</body>
</html>