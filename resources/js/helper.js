export function formatNumber(amount) {
    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'HUF',
        minimumFractionDigits: 0,
    });
      
    return formatter.format(amount).replace('HUF', '');
}

// Appends a dictionary definition to a translation text. Definitions are grouped
// under the name of their dictionary, and the groups are separated by a blank line.
export function appendDefinitionToTranslation(translationText, definition, dictionaryName = '') {
    if (!dictionaryName) {
        if (translationText.length && translationText[translationText.length - 1] !== ';') {
            translationText += ';';
        }

        return translationText + definition;
    }

    const trimmedText = translationText.replace(/[\s;]+$/, '');
    if (!trimmedText.length) {
        return dictionaryName + '\n' + definition;
    }

    // the last group already belongs to this dictionary
    const lastGroup = trimmedText.split(/\n\s*\n/).pop();
    if (lastGroup.startsWith(dictionaryName + '\n')) {
        return trimmedText + ';\n' + definition;
    }

    return trimmedText + ';\n\n' + dictionaryName + '\n' + definition;
}
