import { useEffect, useRef, useState } from 'react';

type TimerOptions = {
    quizSeconds: number | null;
    sectionSeconds: number | null;
    questionSeconds: number | null;
    onQuizExpire?: () => void;
    onSectionExpire?: () => void;
    onQuestionExpire?: () => void;
};

type TimerState = {
    quizRemaining: number | null;
    sectionRemaining: number | null;
    questionRemaining: number | null;
};

type ExpiredTimer = 'quiz' | 'section' | 'question' | null;

export function useQuizTimer({
    quizSeconds,
    sectionSeconds,
    questionSeconds,
    onQuizExpire,
    onSectionExpire,
    onQuestionExpire,
}: TimerOptions): TimerState {
    const [state, setState] = useState<TimerState>({
        quizRemaining: quizSeconds,
        sectionRemaining: sectionSeconds,
        questionRemaining: questionSeconds,
    });

    const expiredRef = useRef<ExpiredTimer>(null);
    const onQuizExpireRef = useRef(onQuizExpire);
    const onSectionExpireRef = useRef(onSectionExpire);
    const onQuestionExpireRef = useRef(onQuestionExpire);

    useEffect(() => {
        onQuizExpireRef.current = onQuizExpire;
        onSectionExpireRef.current = onSectionExpire;
        onQuestionExpireRef.current = onQuestionExpire;
    }, [onQuizExpire, onSectionExpire, onQuestionExpire]);

    useEffect(() => {
        setState({
            quizRemaining: quizSeconds,
            sectionRemaining: sectionSeconds,
            questionRemaining: questionSeconds,
        });
        expiredRef.current = null;
    }, [quizSeconds, sectionSeconds, questionSeconds]);

    useEffect(() => {
        const interval = window.setInterval(() => {
            let expired: ExpiredTimer = null;

            setState((previous) => {
                const next: TimerState = {
                    quizRemaining: previous.quizRemaining === null ? null : Math.max(0, previous.quizRemaining - 1),
                    sectionRemaining:
                        previous.sectionRemaining === null ? null : Math.max(0, previous.sectionRemaining - 1),
                    questionRemaining:
                        previous.questionRemaining === null ? null : Math.max(0, previous.questionRemaining - 1),
                };

                if (expiredRef.current !== null) {
                    return next;
                }

                if (next.quizRemaining === 0) {
                    expired = 'quiz';
                } else if (next.sectionRemaining === 0) {
                    expired = 'section';
                } else if (next.questionRemaining === 0) {
                    expired = 'question';
                }

                return next;
            });

            if (expired === null || expiredRef.current !== null) {
                return;
            }

            expiredRef.current = expired;

            if (expired === 'quiz') {
                onQuizExpireRef.current?.();
            } else if (expired === 'section') {
                onSectionExpireRef.current?.();
            } else {
                onQuestionExpireRef.current?.();
            }
        }, 1000);

        return () => window.clearInterval(interval);
    }, []);

    return state;
}
