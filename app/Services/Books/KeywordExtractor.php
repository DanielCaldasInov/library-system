<?php

namespace App\Services\Books;

class KeywordExtractor
{
    public function extract(string $text, int $limit = 20): array
    {
        $text = mb_strtolower($text);

        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $tokens = array_filter(explode(' ', trim($text)));

        $stop = $this->stopwords();
        $freq = [];

        foreach ($tokens as $t) {
            $t = trim($t);
            if (mb_strlen($t) < 3) continue;
            if (isset($stop[$t])) continue;

            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }

        arsort($freq);

        return array_slice(array_keys($freq), 0, $limit);
    }

    private function stopwords(): array
    {
        $words = [
            'a','o','os','as','um','uma','uns','umas','de','do','da','dos','das',
            'e','ou','em','no','na','nos','nas','por','para','com','sem','sobre',
            'que','se','ao','à','às','aos','sua','seu','suas','seus','este','esta',
            'isso','isto','aquele','aquela','eles','elas','ser','ter','foi','era',
            'the','and','or','to','of','in','on','for','with','without','from','by',
            'is','are','was','were','be','been','it','this','that','these','those',
        ];

        return array_fill_keys($words, true);
    }
}
