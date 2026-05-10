<?php
/**
 * Campus2Career – Skill Matcher
 * Simple keyword-based skill matching
 */
class SkillMatcher {

    /**
     * Returns match percentage (0-100) and matched keywords.
     */
    public static function match(string $studentSkills, string $requirements): array {
        $studentArr = self::tokenize($studentSkills);
        $reqArr     = self::tokenize($requirements);

        if (empty($reqArr)) return ['percent' => 0, 'matched' => [], 'total' => 0];

        $matched = array_intersect($reqArr, $studentArr);
        $percent = (int)round((count($matched) / count($reqArr)) * 100);

        return [
            'percent' => min($percent, 100),
            'matched' => array_values($matched),
            'total'   => count($reqArr),
        ];
    }

    public static function label(int $percent): string {
        if ($percent >= 80) return 'Excellent Match';
        if ($percent >= 60) return 'Good Match';
        if ($percent >= 40) return 'Partial Match';
        if ($percent >= 20) return 'Low Match';
        return 'No Match';
    }

    public static function color(int $percent): string {
        if ($percent >= 80) return '#10B981';
        if ($percent >= 60) return '#3B82F6';
        if ($percent >= 40) return '#F59E0B';
        return '#EF4444';
    }

    private static function tokenize(string $str): array {
        $parts = preg_split('/[\s,;\/\|]+/', strtolower($str));
        return array_filter(array_map('trim', $parts));
    }
}
