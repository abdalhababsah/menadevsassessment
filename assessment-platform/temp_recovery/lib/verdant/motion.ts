import { Variants } from "framer-motion";

export const fadeUp: Variants = {
  initial: { opacity: 0, y: 10 },
  animate: { opacity: 1, y: 0, transition: { duration: 0.3, ease: [0.4, 0, 0.2, 1] } },
  exit: { opacity: 0, y: 10 }
};

export const scaleIn: Variants = {
  initial: { opacity: 0, scale: 0.95 },
  animate: { opacity: 1, scale: 1, transition: { duration: 0.2, ease: [0.4, 0, 0.2, 1] } },
  exit: { opacity: 0, scale: 0.95 }
};

export const staggerChildren = (staggerDelay = 0.05) => ({
  animate: {
    transition: {
      staggerChildren: staggerDelay
    }
  }
});
