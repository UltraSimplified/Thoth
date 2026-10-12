/*
 * What each sephira and path means on the Tree of Life. The path numbers, letters and the sephiroth they join
 * mirror App\Services\TreePlacement::PATHS on the server. The text is a summary of standard Qabalistic
 * correspondences, not a quotation from a single source.
 */

export interface SephiraInfo {
    key: string;
    name: string;
    number: number;
    /** The name's meaning, e.g. "Crown" for Kether. */
    meaning: string;
    pillar: string;
    triad: string;
    symbolism: string;
}

export interface PathInfo {
    number: number;
    letter: string;
    /** What the Hebrew letter means, e.g. "mouth" for Peh. */
    letterMeaning: string;
    /** The Thoth card on this path. */
    card: string;
    from: string;
    to: string;
    symbolism: string;
}

export const SEPHIRA_INFO: Record<string, SephiraInfo> = {
    kether: {
        key: 'kether',
        name: 'Kether',
        number: 1,
        meaning: 'Crown',
        pillar: 'Middle',
        triad: 'Supernal',
        symbolism:
            'The first point of manifestation: pure unity and the will from which everything else unfolds. Nothing is yet divided.',
    },
    chokmah: {
        key: 'chokmah',
        name: 'Chokmah',
        number: 2,
        meaning: 'Wisdom',
        pillar: 'Mercy',
        triad: 'Supernal',
        symbolism:
            'The first flash of an idea, before it has any form. An outgoing, active force.',
    },
    binah: {
        key: 'binah',
        name: 'Binah',
        number: 3,
        meaning: 'Understanding',
        pillar: 'Severity',
        triad: 'Supernal',
        symbolism:
            'The receiving, form-giving force that takes the raw idea and gives it shape and limits.',
    },
    chesed: {
        key: 'chesed',
        name: 'Chesed',
        number: 4,
        meaning: 'Mercy',
        pillar: 'Mercy',
        triad: 'Ethical',
        symbolism:
            'Expansion, generosity and order. Form is extended outward and abundance flows.',
    },
    geburah: {
        key: 'geburah',
        name: 'Geburah',
        number: 5,
        meaning: 'Severity',
        pillar: 'Severity',
        triad: 'Ethical',
        symbolism:
            'Contraction, judgement and discipline. The force that cuts away what no longer serves.',
    },
    tiphareth: {
        key: 'tiphareth',
        name: 'Tiphareth',
        number: 6,
        meaning: 'Beauty',
        pillar: 'Middle',
        triad: 'Ethical',
        symbolism:
            'Balance between mercy and severity, at the centre of the Tree. The self and the heart, where the other sephiroth meet.',
    },
    netzach: {
        key: 'netzach',
        name: 'Netzach',
        number: 7,
        meaning: 'Victory',
        pillar: 'Mercy',
        triad: 'Astral',
        symbolism:
            'Desire, feeling and instinct, and the drive to endure. Nature and the emotional life.',
    },
    hod: {
        key: 'hod',
        name: 'Hod',
        number: 8,
        meaning: 'Splendour',
        pillar: 'Severity',
        triad: 'Astral',
        symbolism:
            'Intellect, logic, language and ritual. The mind giving form to what it perceives.',
    },
    yesod: {
        key: 'yesod',
        name: 'Yesod',
        number: 9,
        meaning: 'Foundation',
        pillar: 'Middle',
        triad: 'Astral',
        symbolism:
            'The astral level: dreams, imagination and the reservoir that links the Tree to the material world.',
    },
    malkuth: {
        key: 'malkuth',
        name: 'Malkuth',
        number: 10,
        meaning: 'Kingdom',
        pillar: 'Middle',
        triad: 'Malkuth',
        symbolism:
            'The physical world, the body and the here and now, where the whole descent ends.',
    },
};

export const PATH_INFO: PathInfo[] = [
    {
        number: 11,
        letter: 'Aleph',
        letterMeaning: 'ox',
        card: 'The Fool',
        from: 'kether',
        to: 'chokmah',
        symbolism:
            'The first step out of unity: breath and air, the untested beginning of the journey.',
    },
    {
        number: 12,
        letter: 'Beth',
        letterMeaning: 'house',
        card: 'The Magus',
        from: 'kether',
        to: 'binah',
        symbolism:
            'The will made into a dwelling place. Skill and communication, shaped into a vessel.',
    },
    {
        number: 13,
        letter: 'Gimel',
        letterMeaning: 'camel',
        card: 'The Priestess',
        from: 'kether',
        to: 'tiphareth',
        symbolism:
            'Carrying the light from the crown down to the heart. Intuition and what is hidden.',
    },
    {
        number: 14,
        letter: 'Daleth',
        letterMeaning: 'door',
        card: 'The Empress',
        from: 'chokmah',
        to: 'binah',
        symbolism:
            'The door through which wisdom becomes form. Creation, fertility and nurture.',
    },
    {
        number: 15,
        letter: 'Heh',
        letterMeaning: 'window',
        card: 'The Star',
        from: 'chokmah',
        to: 'tiphareth',
        symbolism:
            'A window that lets light in. Hope, guidance and a star to steer by.',
    },
    {
        number: 16,
        letter: 'Vav',
        letterMeaning: 'nail, hook',
        card: 'The Hierophant',
        from: 'chokmah',
        to: 'chesed',
        symbolism:
            'The bond between wisdom and mercy. Teaching, tradition and the link between heaven and earth.',
    },
    {
        number: 17,
        letter: 'Zain',
        letterMeaning: 'sword',
        card: 'The Lovers',
        from: 'binah',
        to: 'tiphareth',
        symbolism:
            'Discrimination and choice: dividing and joining, and the decision between two ways.',
    },
    {
        number: 18,
        letter: 'Cheth',
        letterMeaning: 'fence',
        card: 'The Chariot',
        from: 'binah',
        to: 'geburah',
        symbolism:
            'An enclosure that protects. The will moving forward within bounds, and victory through control.',
    },
    {
        number: 19,
        letter: 'Teth',
        letterMeaning: 'serpent',
        card: 'Lust',
        from: 'chesed',
        to: 'geburah',
        symbolism:
            'The coiled energy of life. Passion and vitality, held in balance between mercy and severity.',
    },
    {
        number: 20,
        letter: 'Yod',
        letterMeaning: 'hand',
        card: 'The Hermit',
        from: 'chesed',
        to: 'tiphareth',
        symbolism:
            'The seed of creation, and a lamp carried alone. A search that turns inward.',
    },
    {
        number: 21,
        letter: 'Kaph',
        letterMeaning: 'palm',
        card: 'Fortune',
        from: 'chesed',
        to: 'netzach',
        symbolism:
            'Open hands that give and receive. The turning wheel and the cycles of change.',
    },
    {
        number: 22,
        letter: 'Lamed',
        letterMeaning: 'ox-goad',
        card: 'Adjustment',
        from: 'geburah',
        to: 'tiphareth',
        symbolism:
            'The goad that directs. Balance, law and corrective judgement.',
    },
    {
        number: 23,
        letter: 'Mem',
        letterMeaning: 'water',
        card: 'The Hanged Man',
        from: 'geburah',
        to: 'hod',
        symbolism:
            'Suspension and surrender. Seeing from a new angle, and the sacrifice that brings understanding.',
    },
    {
        number: 24,
        letter: 'Nun',
        letterMeaning: 'fish',
        card: 'Death',
        from: 'tiphareth',
        to: 'netzach',
        symbolism:
            'That which swims in the depths and rises again. Transformation and renewal.',
    },
    {
        number: 25,
        letter: 'Samekh',
        letterMeaning: 'prop, support',
        card: 'Art',
        from: 'tiphareth',
        to: 'yesod',
        symbolism:
            'Support and tempering: opposites blended into one. The alchemist’s work.',
    },
    {
        number: 26,
        letter: 'Ayin',
        letterMeaning: 'eye',
        card: 'The Devil',
        from: 'tiphareth',
        to: 'hod',
        symbolism:
            'Seeing material things as they are. The shadow of appetite and the pull of illusion.',
    },
    {
        number: 27,
        letter: 'Peh',
        letterMeaning: 'mouth',
        card: 'The Tower',
        from: 'netzach',
        to: 'hod',
        symbolism:
            'Speech, and the moment it breaks through. Sudden revelation and the collapse of false structures.',
    },
    {
        number: 28,
        letter: 'Tzaddi',
        letterMeaning: 'fish-hook',
        card: 'The Emperor',
        from: 'netzach',
        to: 'yesod',
        symbolism:
            'Drawing up what is hidden. Authority and structure, tied to the astral and the dream.',
    },
    {
        number: 29,
        letter: 'Qoph',
        letterMeaning: 'back of the head',
        card: 'The Moon',
        from: 'netzach',
        to: 'malkuth',
        symbolism:
            'What is seen only by reflection. The unconscious, and the path from desire into the world.',
    },
    {
        number: 30,
        letter: 'Resh',
        letterMeaning: 'head',
        card: 'The Sun',
        from: 'hod',
        to: 'yesod',
        symbolism:
            'Illumination and clarity of mind. Vitality and a healthy, conscious self.',
    },
    {
        number: 31,
        letter: 'Shin',
        letterMeaning: 'tooth',
        card: 'The Aeon',
        from: 'hod',
        to: 'malkuth',
        symbolism:
            'Fire: the force that breaks down and transforms. A new age, with spirit renewing matter.',
    },
    {
        number: 32,
        letter: 'Tav',
        letterMeaning: 'mark, cross',
        card: 'The Universe',
        from: 'yesod',
        to: 'malkuth',
        symbolism:
            'The seal and the completed cycle. The last step from the astral into the material world.',
    },
];
