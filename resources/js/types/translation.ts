declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $t: (key: string, replacements?: Record<string, string | number>) => string;
    }
}

export {};
