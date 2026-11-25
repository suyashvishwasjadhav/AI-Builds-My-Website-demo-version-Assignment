import Alpine from 'alpinejs';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

window.Alpine = Alpine;
Alpine.start();

gsap.registerPlugin(ScrollTrigger);

document.addEventListener('DOMContentLoaded', () => {
    const isTemplatePage = document.querySelector('main') && (document.querySelector('.tpl-card') || document.querySelector('.tpl-fade-up'));
    if (!isTemplatePage) {
        return;
    }

    const mm = gsap.matchMedia();

    mm.add('(prefers-reduced-motion: no-preference)', () => {
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            smoothWheel: true,
        });

        lenis.on('scroll', ScrollTrigger.update);

        const tickerFn = (time) => {
            lenis.raf(time * 1000);
        };
        gsap.ticker.add(tickerFn);
        gsap.ticker.lagSmoothing(0);

        // Smooth anchor navigation with Lenis
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', (e) => {
                const href = anchor.getAttribute('href');
                if (!href || href === '#') {
                    e.preventDefault();
                    lenis.scrollTo(0);
                    return;
                }
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    lenis.scrollTo(target, { offset: -72 });
                }
            });
        });

        // Hero entrance
        gsap.from('.tpl-fade-up, .tpl-fade-up-delay', {
            y: 26,
            opacity: 0,
            duration: 0.85,
            stagger: 0.12,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
        });

        // Sections reveal on scroll
        gsap.utils.toArray('main section').forEach((section) => {
            gsap.from(section, {
                y: 30,
                opacity: 0,
                duration: 0.75,
                ease: 'power3.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: section,
                    start: 'top 86%',
                    once: true,
                },
            });
        });

        // Staggered service / gallery cards
        const cards = gsap.utils.toArray('.tpl-card');
        if (cards.length > 0) {
            gsap.from(cards, {
                y: 24,
                opacity: 0,
                duration: 0.65,
                stagger: 0.08,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
                scrollTrigger: {
                    trigger: cards[0],
                    start: 'top 86%',
                    once: true,
                },
            });
        }

        return () => {
            gsap.ticker.remove(tickerFn);
            lenis.destroy();
        };
    });
});
