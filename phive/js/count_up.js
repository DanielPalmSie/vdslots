class CountUpAnimation {
    /**
     * @param {number} animationDuration animation duration in ms
     * @param {number} countFromPercent percent from which we start counting (specify 0 to start counting from beginning)
     */
    constructor(animationDuration = 2000, countFromPercent = 70) {
        this.animationDuration = animationDuration;
        this.frameDuration = 1000 / 60;
        this.totalFrames = Math.round(this.animationDuration / this.frameDuration);
        this.countFromPercent = countFromPercent;
    }

    animateCountUp(el) {
        const currencySymbol = el.innerHTML.trim().split(' ')[0];

        let number = this._extractNumber(el.innerHTML);
        const countTo = parseFloat(number, 10);
        const countFrom = countTo * (this.countFromPercent / 100);

        let frame = 0;

        const onFrame = () => {
            frame++;
            // Calculate our progress as a value between 0 and 1
            // Pass that value to our easing function to get our
            // progress on a curve
            const progress = this._easeOutQuad(frame / this.totalFrames);

            // Use the progress value to calculate the current count
            const currentCount = (countTo - countFrom) * progress + countFrom;

            number = this._extractNumber(el.innerHTML);

            // If the current count has changed, update the element
            if (Math.abs(parseFloat(number) - currentCount) > 0.01) {
                const formattedNumber =  new Intl
                    .NumberFormat(undefined, { minimumFractionDigits: 2 })
                    .format(currentCount);

                el.innerHTML = currencySymbol + ' ' + formattedNumber;
            }

            // Run animation until we reach last frame
            if (frame < this.totalFrames) {
                requestAnimationFrame(onFrame);
            }
        }

        onFrame();
    }

    _easeOutQuad(t) {
        return t * (2 - t);
    }

    _extractNumber(str) {
        return str.replace(/[^0-9.]+/g, '');
    }
}
