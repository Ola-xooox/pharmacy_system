<?php
header('Content-Type: application/json');
require '../db_connect.php';

// Set the timezone to your local timezone
date_default_timezone_set('Asia/Manila');

// Get the period parameter (weekly, monthly, yearly)
$period = isset($_GET['period']) ? $_GET['period'] : 'weekly';

$chartData = [];

try {
    if ($period === 'weekly') {
        // Last 7 days
        $stmt = $conn->prepare("
            SELECT DATE(transaction_date) as date, SUM(total_price) as total_sales
            FROM purchase_history
            WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(transaction_date)
            ORDER BY DATE(transaction_date) ASC
        ");
        $stmt->execute();
        $rawData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Prepare data for last 7 days
        $labels = [];
        $salesData = [];
        $period = new DatePeriod(new DateTime('-6 days'), new DateInterval('P1D'), new DateTime('+1 day'));
        
        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $labels[] = $date->format('D'); // Day of the week
            $found = false;
            foreach ($rawData as $row) {
                if ($row['date'] === $formattedDate) {
                    $salesData[] = floatval($row['total_sales']);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $salesData[] = 0;
            }
        }
        
        $chartData = [
            'labels' => $labels,
            'data' => $salesData,
            'title' => 'Sales Performance (Last 7 Days)'
        ];
        
    } elseif ($period === 'monthly') {
        // Last 12 months
        $stmt = $conn->prepare("
            SELECT 
                YEAR(transaction_date) as year,
                MONTH(transaction_date) as month,
                SUM(total_price) as total_sales
            FROM purchase_history
            WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
            GROUP BY YEAR(transaction_date), MONTH(transaction_date)
            ORDER BY YEAR(transaction_date) ASC, MONTH(transaction_date) ASC
        ");
        $stmt->execute();
        $rawData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Prepare data for last 12 months
        $labels = [];
        $salesData = [];
        $startDate = new DateTime('-11 months');
        $startDate->modify('first day of this month');
        
        for ($i = 0; $i < 12; $i++) {
            $currentMonth = clone $startDate;
            $currentMonth->add(new DateInterval('P' . $i . 'M'));
            
            $year = $currentMonth->format('Y');
            $month = $currentMonth->format('n');
            $labels[] = $currentMonth->format('M Y');
            
            $found = false;
            foreach ($rawData as $row) {
                if ($row['year'] == $year && $row['month'] == $month) {
                    $salesData[] = floatval($row['total_sales']);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $salesData[] = 0;
            }
        }
        
        $chartData = [
            'labels' => $labels,
            'data' => $salesData,
            'title' => 'Sales Performance (Last 12 Months)'
        ];
        
    } elseif ($period === 'yearly') {
        // Last 5 years
        $stmt = $conn->prepare("
            SELECT 
                YEAR(transaction_date) as year,
                SUM(total_price) as total_sales
            FROM purchase_history
            WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR)
            GROUP BY YEAR(transaction_date)
            ORDER BY YEAR(transaction_date) ASC
        ");
        $stmt->execute();
        $rawData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Prepare data for last 5 years
        $labels = [];
        $salesData = [];
        $currentYear = date('Y');
        
        for ($i = 4; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $labels[] = $year;
            
            $found = false;
            foreach ($rawData as $row) {
                if ($row['year'] == $year) {
                    $salesData[] = floatval($row['total_sales']);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $salesData[] = 0;
            }
        }
        
        $chartData = [
            'labels' => $labels,
            'data' => $salesData,
            'title' => 'Sales Performance (Last 5 Years)'
        ];
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $chartData = [
        'error' => 'Failed to fetch chart data: ' . $e->getMessage()
    ];
}

$conn->close();

echo json_encode($chartData);
?>
