<?php

namespace App\Import;

/**
 * Retrouve un cas d'enum à partir du libellé affiché dans Notion,
 * sans tenir compte des emojis, des accents ni de la casse.
 */
final class LibelleNotion
{
    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     *
     * @return T|null
     */
    public static function versEnum(string $enum, ?string $libelle): ?\BackedEnum
    {
        $cible = self::normaliser($libelle ?? '');
        if ('' === $cible) {
            return null;
        }

        foreach ($enum::cases() as $cas) {
            if (self::normaliser($cas->label()) === $cible) {
                return $cas;
            }
        }

        return null;
    }

    public static function normaliser(string $texte): string
    {
        // Ne garde que la partie avant un tiret long : "❄️ Froid — suspect" → "froid".
        $texte = explode('—', $texte)[0];
        $texte = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $texte) ?: $texte;

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $texte));
    }
}
