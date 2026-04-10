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

    const quizCalled = useRef(false);
    const sectionCalled = useRef(false);
    const questionCalled = useRef(false);

    useEffect(() => {
        setState({
            quizRemaining: quizSeconds,
            sectionRemaining: sectionSeconds,
            questionRemaining: questionSeconds,
        });
        quizCalled.current = false;
        sectionCalled.current = false;
        questionCalled.current = false;
    }, [quizSeconds, sectionSeconds, questionSeconds]);

    useEffect(() => {
        const interval = setInterval(() => {
            setState((prev) => {
                const next: TimerState = { ...prev };

                if (next.quizRemaining !== null) {
                    next.quizRemaining = Math.max(0, next.quizRemaining - 1);
                    if (next.quizRemaining === 0 && !quizCalled.current) {
                        quizCalled.current = true;
                        onQuizExpire?.();
                    }
                }

                if (next.sectionRemaining !== null) {
                    next.sectionRemaining = Math.max(0, next.sectionRemaining - 1);
                    if (next.sectionRemaining === 0 && !sectionCalled.current) {
                        sectionCalled.current = true;
                        onSectionExpire?.();
                    }
                }

                if (next.questionRemaining !== null) {
                    next.questionRemaining = Math.max(0, next.questionRemaining - 1);
                    if (next.questionRemaining === 0 && !questionCalled.current) {
                        questionCalled.current = true;
                        onQuestionExpire?.();
                    }
                }

                return next;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [onQuizExpire, onSectionExpire, onQuestionExpire]);

    return state;
}
