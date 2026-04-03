<?php
/**
 * Shared Grading Function
 * Centralized grading scale used across the system
 * Converts numerical marks to letter grades with colors
 */

function getGrade($marks) {
    if ($marks >= 90) return ['grade' => 'A+', 'color' => '#10b981'];      // A+ (90-100)
    if ($marks >= 85) return ['grade' => 'A', 'color' => '#059669'];       // A  (85-89)
    if ($marks >= 80) return ['grade' => 'A-', 'color' => '#0d9488'];      // A- (80-84)
    if ($marks >= 75) return ['grade' => 'B+', 'color' => '#2563eb'];      // B+ (75-79)
    if ($marks >= 70) return ['grade' => 'B', 'color' => '#1e40af'];       // B  (70-74)
    if ($marks >= 65) return ['grade' => 'B-', 'color' => '#1e3a8a'];      // B- (65-69)
    if ($marks >= 60) return ['grade' => 'C+', 'color' => '#ea580c'];      // C+ (60-64)
    if ($marks >= 55) return ['grade' => 'C', 'color' => '#c2410c'];       // C  (55-59)
    if ($marks >= 50) return ['grade' => 'C-', 'color' => '#b45309'];      // C- (50-54)
    if ($marks >= 40) return ['grade' => 'D', 'color' => '#ea8500'];       // D  (40-49)
    return ['grade' => 'F', 'color' => '#dc2626'];                         // F  (0-39)
}
?>
