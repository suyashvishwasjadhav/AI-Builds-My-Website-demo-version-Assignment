<style>
    @media (prefers-reduced-motion: no-preference) {
        .tpl-btn {
            position: relative;
            overflow: hidden;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease, background-color 0.2s ease;
        }
        .tpl-btn::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 20%, rgba(255, 255, 255, 0.24) 50%, transparent 80%);
            transform: translateX(-110%);
            transition: transform 0.6s ease;
            pointer-events: none;
        }
        .tpl-btn:hover::after {
            transform: translateX(110%);
        }
        .tpl-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -10px rgba(15, 23, 42, 0.35);
        }
        .tpl-btn:active {
            transform: scale(0.97);
        }
        .tpl-card {
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .tpl-card:hover {
            transform: translateY(-4px);
        }
    }
</style>
