export type Suit = 'wands' | 'cups' | 'swords' | 'disks';

export interface CardData {
    id: number;
    slug: string;
    name: string;
    title: string | null;
    arcana: 'major' | 'minor';
    suit: Suit | null;
    suitLabel: string | null;
    world: string | null;
    rank: string | null;
    number: number;
    roman: string | null;
    element: string | null;
    subElement: string | null;
    hebrewLetter: string | null;
    planet: string | null;
    sign: string | null;
    decan: number | null;
    zodiacSpan: { from: string; to: string } | null;
    quadrant: string | null;
    sephira: string | null;
    sephiraLabel: string | null;
    treePath: number | null;
    thothreadingsUrl: string;
    personalNote: string | null;
    imageUrl: string;
    thumbnailUrl: string;
    birthRoles: string[];
}

/** Nodes are sephira keys such as "binah"; paths are path numbers 11 to 32. */
export interface TreeHighlights {
    nodes: string[];
    paths: number[];
}

export interface ReadingData {
    question: string | null;
    interpretationNotes: string | null;
    card: CardData;
    jumpers: CardData[];
    clarifiers: CardData[];
}

export interface DayData {
    id: number;
    eventNote: string | null;
    outcomeNote: string | null;
    resonated: boolean | null;
    themes: string[];
    reading: ReadingData | null;
}

export interface DayPageProps {
    date: string;
    dateLabel: string;
    isToday: boolean;
    day: DayData | null;
    locks: {
        morning: boolean;
        evening: boolean;
        morningUntil: string;
        eveningUntil: string;
    };
    cards: CardData[];
    themeNames: string[];
    tree: {
        main: TreeHighlights;
        jumpers: TreeHighlights;
        clarifiers: TreeHighlights;
        birth: TreeHighlights;
    };
    journalUrl: string;
    showJournalReminder: boolean;
}

export interface Appearance {
    date: string;
    dateLabel: string;
    role: 'main' | 'jumper' | 'clarifier';
    question: string | null;
    eventNote: string | null;
    url: string;
}

export type TreePosition =
    | {
          kind: 'sephira';
          sephira: string;
          label: string;
          number: number;
          pillar: string;
          triad: string;
      }
    | {
          kind: 'path';
          number: number;
          letter: string;
          from: string;
          to: string;
      };
