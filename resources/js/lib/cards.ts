import type { CardData } from '@/types/deck';

export function capitalise(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

/** Court domains open at 21 degrees of their first sign and close at 20 degrees of the next. */
export function zodiacSpanLabel(span: { from: string; to: string }): string {
    return `21° ${span.from} to 20° ${span.to}`;
}

/**
 * Whether a card matches the picker's search. Every word must match: a number matches the card number
 * exactly ("5 wands" is the five of wands), a Roman numeral matches majors exactly, and any other word
 * matches part of the name, title, rank or suit.
 */
export function matchesCardQuery(card: CardData, query: string): boolean {
    const words = query.trim().toLowerCase().split(/\s+/).filter(Boolean);

    if (words.length === 0) {
        return true;
    }

    const text = [
        card.name,
        card.title,
        card.rank,
        card.suitLabel,
        card.world,
        card.quadrant,
    ]
        .filter((part): part is string => part !== null)
        .join(' ')
        .toLowerCase();

    return words.every((word) => {
        if (/^\d+$/.test(word)) {
            return card.number === Number(word);
        }

        if (card.roman !== null && word === card.roman.toLowerCase()) {
            return true;
        }

        return text.includes(word);
    });
}
