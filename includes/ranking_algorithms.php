<?php
/**
 * Student Ranking Algorithms
 * Different sorting approaches with varying time complexities
 */

class StudentRankingAlgorithms {
    
    /**
     * ALGORITHM 1: PHP Built-in Sort (usort) - RECOMMENDED FOR MOST CASES
     * Time Complexity: O(n log n)
     * Space Complexity: O(n)
     * Use Case: General purpose, reliable, and simple
     * 
     * @param array $students - Array of student data with marks_obtained and student_id
     * @param string $currentStudentId - Current student ID to find rank
     * @return array - ['rank' => rank, 'total' => total_students]
     */
    public static function rankByUSort($students, $currentStudentId) {
        if (empty($students)) {
            return ['rank' => 0, 'total' => 0];
        }

        // Sort by marks descending (highest first)
        usort($students, function($a, $b) {
            $marksA = floatval($a['avg_marks']);
            $marksB = floatval($b['avg_marks']);
            
            if ($marksA === $marksB) return 0;
            return ($marksA > $marksB) ? -1 : 1;
        });

        // Find current student's rank
        $rank = 0;
        $total = count($students);
        
        foreach ($students as $index => $student) {
            if ($student['student_id'] == $currentStudentId) {
                $rank = $index + 1;
                break;
            }
        }

        return ['rank' => $rank, 'total' => $total, 'sorted_students' => $students];
    }

    /**
     * ALGORITHM 2: Quick Sort
     * Time Complexity: O(n log n) average, O(n²) worst case
     * Space Complexity: O(log n) due to recursion
     * Use Case: General purpose, often fastest in practice
     * 
     * @param array $students - Array of student data
     * @param int $low - Lower bound
     * @param int $high - Upper bound
     * @return array - Sorted array
     */
    public static function quickSort(&$students, $low = 0, $high = null) {
        if ($high === null) {
            $high = count($students) - 1;
        }

        if ($low < $high) {
            $pi = self::partition($students, $low, $high);
            self::quickSort($students, $low, $pi - 1);
            self::quickSort($students, $pi + 1, $high);
        }

        return $students;
    }

    private static function partition(&$students, $low, $high) {
        $pivot = floatval($students[$high]['avg_marks']);
        $i = $low - 1;

        for ($j = $low; $j < $high; $j++) {
            if (floatval($students[$j]['avg_marks']) >= $pivot) { // Descending order
                $i++;
                // Swap
                $temp = $students[$i];
                $students[$i] = $students[$j];
                $students[$j] = $temp;
            }
        }
        // Swap final pivot
        $temp = $students[$i + 1];
        $students[$i + 1] = $students[$high];
        $students[$high] = $temp;

        return $i + 1;
    }

    /**
     * ALGORITHM 3: Merge Sort
     * Time Complexity: O(n log n) - Guaranteed
     * Space Complexity: O(n)
     * Use Case: When stable sort is required, guaranteed performance
     * 
     * @param array $students - Array of student data
     * @return array - Sorted array
     */
    public static function mergeSort($students) {
        if (count($students) <= 1) {
            return $students;
        }

        $mid = intdiv(count($students), 2);
        $left = array_slice($students, 0, $mid);
        $right = array_slice($students, $mid);

        $left = self::mergeSort($left);
        $right = self::mergeSort($right);

        return self::merge($left, $right);
    }

    private static function merge($left, $right) {
        $result = [];
        $i = $j = 0;

        while ($i < count($left) && $j < count($right)) {
            if (floatval($left[$i]['avg_marks']) >= floatval($right[$j]['avg_marks'])) {
                $result[] = $left[$i];
                $i++;
            } else {
                $result[] = $right[$j];
                $j++;
            }
        }

        // Merge remaining elements
        while ($i < count($left)) {
            $result[] = $left[$i];
            $i++;
        }

        while ($j < count($right)) {
            $result[] = $right[$j];
            $j++;
        }

        return $result;
    }

    /**
     * ALGORITHM 4: Heap Sort
     * Time Complexity: O(n log n) - Guaranteed
     * Space Complexity: O(1) - In-place
     * Use Case: Memory constrained environments
     * 
     * @param array $students - Array of student data
     * @return array - Sorted array
     */
    public static function heapSort(&$students) {
        $n = count($students);

        // Build max heap
        for ($i = intdiv($n, 2) - 1; $i >= 0; $i--) {
            self::heapify($students, $n, $i);
        }

        // One by one extract element from heap
        for ($i = $n - 1; $i > 0; $i--) {
            // Swap
            $temp = $students[0];
            $students[0] = $students[$i];
            $students[$i] = $temp;

            self::heapify($students, $i, 0);
        }

        return $students;
    }

    private static function heapify(&$students, $n, $i) {
        $largest = $i;
        $left = 2 * $i + 1;
        $right = 2 * $i + 2;

        if ($left < $n && floatval($students[$left]['avg_marks']) > floatval($students[$largest]['avg_marks'])) {
            $largest = $left;
        }

        if ($right < $n && floatval($students[$right]['avg_marks']) > floatval($students[$largest]['avg_marks'])) {
            $largest = $right;
        }

        if ($largest !== $i) {
            $temp = $students[$i];
            $students[$i] = $students[$largest];
            $students[$largest] = $temp;

            self::heapify($students, $n, $largest);
        }
    }

    /**
     * ALGORITHM 5: Counting Sort (for mark ranges 0-100)
     * Time Complexity: O(n + k) where k = range of marks
     * Space Complexity: O(k)
     * Use Case: When marks are in a limited range (e.g., 0-100)
     * 
     * @param array $students - Array of student data
     * @return array - Sorted array
     */
    public static function countingSort($students) {
        if (empty($students)) {
            return $students;
        }

        $maxMarks = 100; // Assuming marks are out of 100
        $count = array_fill(0, $maxMarks + 1, []);

        // Place students in count array
        foreach ($students as $student) {
            $marks = intval($student['avg_marks']);
            $count[$marks][] = $student;
        }
        // Rebuild sorted array (descending)
        $result = [];
        for ($i = $maxMarks; $i >= 0; $i--) {
            foreach ($count[$i] as $student) {
                $result[] = $student;
            }
        }

        return $result;
    }

    /**
     * Get ranking with performance metrics
     * 
     * @param array $students - Array of student data
     * @param string $currentStudentId - Current student ID
     * @param string $algorithm - Algorithm to use: 'usort', 'quicksort', 'mergesort', 'heapsort', 'countingsort'
     * @return array - Ranking result with metrics
     */
    public static function getRankWithMetrics($students, $currentStudentId, $algorithm = 'usort') {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = [];

        switch ($algorithm) {
            case 'quicksort':
                $sorted = $students;
                self::quickSort($sorted);
                break;
            case 'mergesort':
                $sorted = self::mergeSort($students);
                break;
            case 'heapsort':
                $sorted = $students;
                self::heapSort($sorted);
                break;
            case 'countingsort':
                $sorted = self::countingSort($students);
                break;
            default: // usort
                $sorted = $students;
                usort($sorted, function($a, $b) {
                    $marksA = floatval($a['avg_marks']);
                    $marksB = floatval($b['avg_marks']);
                    if ($marksA === $marksB) return 0;
                    return ($marksA > $marksB) ? -1 : 1;
                });
        }

        // Find rank
        $rank = 0;
        foreach ($sorted as $index => $student) {
            if ($student['student_id'] == $currentStudentId) {
                $rank = $index + 1;
                break;
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        return [
            'rank' => $rank,
            'total' => count($students),
            'algorithm' => $algorithm,
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 4),
            'memory_used_kb' => round(($endMemory - $startMemory) / 1024, 2),
            'sorted_students' => $sorted
        ];
    }
}
?>
