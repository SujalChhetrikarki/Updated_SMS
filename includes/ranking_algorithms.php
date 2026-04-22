<?php

class StudentRankingAlgorithms {
    /**
     * Counting Sort - LINEAR TIME RANKING ALGORITHM
     * Optimized for marks in range 0-100
     * 
     * @param array $students - Array of student data with avg_marks and student_id
     * @return array - Sorted array by marks (descending)
     */
    private static function countingSort($students) {
        if (empty($students)) {
            return $students;
        }
        $maxMarks = 100; // Marks range: 0-100
        $count = array_fill(0, $maxMarks + 1, []);
        // Place students in count array based on marks
        foreach ($students as $student) {
            $marks = intval($student['avg_marks']);
            $count[$marks][] = $student;
        }

        // Rebuild sorted array (descending - highest marks first)
        $result = [];
        for ($i = $maxMarks; $i >= 0; $i--) {
            foreach ($count[$i] as $student) {
                $result[] = $student;
            }
        }

        return $result;
    }

    /**
     * Get student rank in class using Counting Sort algorithm
     * 
     * @param array $students - Array of student data
     * @param string $currentStudentId - Current student ID to find rank
     * @return array - ['rank' => rank, 'total' => total_students, 'sorted_students' => sorted_array]
     */
    public static function getStudentRank($students, $currentStudentId) {
        if (empty($students)) {
            return ['rank' => 0, 'total' => 0, 'sorted_students' => []];
        }

        $startTime = microtime(true);
        
        // Sort students using Counting Sort
        $sorted_students = self::countingSort($students);
        
        // Find current student's rank
        $rank = 0;
        foreach ($sorted_students as $index => $student) {
            if ($student['student_id'] == $currentStudentId) {
                $rank = $index + 1;
                break;
            }
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 4);
        return [
            'rank' => $rank,
            'total' => count($students),
            'sorted_students' => $sorted_students,
            'execution_time_ms' => $executionTime,
            'algorithm' => 'Counting Sort (Linear O(n))'
        ];
    }

    /**
     * Legacy method for backward compatibility
     * Redirects to new getStudentRank method
     */
    public static function getRankWithMetrics($students, $currentStudentId, $algorithm = 'countingsort') {
        return self::getStudentRank($students, $currentStudentId);
    }
}
?>
