import { usePage } from '@inertiajs/vue3';

type TranslationTree = Record<string, unknown>;

function resolve(tree: TranslationTree, key: string): unknown {
    return key.split('.').reduce<unknown>((acc, segment) => {
        if (acc !== null && typeof acc === 'object' && segment in (acc as Record<string, unknown>)) {
            return (acc as Record<string, unknown>)[segment];
        }

        return undefined;
    }, tree);
}

/**
 * Resolves a dot-path key (e.g. "common.save") against the translation tree
 * shared from Laravel's lang/{locale}/*.php files via Inertia. Missing keys
 * return the key itself rather than throwing, so an untranslated string is
 * visibly obvious instead of crashing the page.
 */
export function useTranslation() {
    const page = usePage<{ translations: TranslationTree }>();

    function t(key: string, replacements: Record<string, string | number> = {}): string {
        const value = resolve(page.props.translations ?? {}, key);

        if (typeof value !== 'string') {
            return key;
        }

        return Object.entries(replacements).reduce((result, [name, replacement]) => result.replaceAll(`:${name}`, String(replacement)), value);
    }

    return { t };
}
