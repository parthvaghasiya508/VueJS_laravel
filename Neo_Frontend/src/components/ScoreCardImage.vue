<template>
  <div class="widget score-card-image">
    <div class="score-card-image-text">
      <h3 class="score-card-image-num" ref="previewText" :style="text" v-html="previewText"></h3>
      <p class="score-card-image-title">{{title}}</p>
    </div>
    <div v-if="imgUrl" class="score-card-image-svg">
      <img :src="imgUrl" alt="icon"/>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'ScoreCardImage',
    props: {
      score: {
        type: String,
        default: null,
        required: true,
      },
      title: {
        type: String,
        default: null,
        required: true,
      },
      imgUrl: {
        type: String,
        required: true,
      },
      currency: {
        type: String,
        default: null,
      },
    },
    data: () => ({
      originalFontSize: 73,
      originalHeight: 100,
      previewHeight: 100,
      previewVertOffset: 0,
      safeWidth: 220,
      container: {
        height: 0,
      },
      text: {
        fontSize: '35px',
        fontWeight: '600',
        position: 'absolute',
        overflow: 'hidden',
        top: 30,
        left: 10,
      },
    }),
    computed: {
      isCurrency() {
        return this.currency !== null;
      },
      previewText() {
        const text = !this.isCurrency ? this.formatScore(this.score) : `${this.formatScore(this.score)} ${this.currency}`;
        return this.nl2br(this.htmlEntities(text));
      },
    },
    mounted() {
      requestAnimationFrame(this.render);
    },
    methods: {
      render() {
        // this.keepRatio();
        // this.scaleText();
        requestAnimationFrame(this.render);
      },
      /**
       * Maintain the aspect ratio of the container
       */
      keepRatio() {
        const newHeight = Math.round(this.$el.clientWidth * (6 / 16));
        this.previewHeight = newHeight;
        this.container.height = `${newHeight}px`;
      },
      /**
       * Scale the previewText element to fit the previewContainer
       */
      scaleText() {
        let newTextScale = 1;
        const previewScale = this.previewHeight / 160;

        // If the current text does not fit inside the "safeWidth" bounds of the default container, scale it
        const currentTextWidth = this.textWidth();

        if (currentTextWidth > this.safeWidth) {
          newTextScale = this.safeWidth / currentTextWidth;
        }

        // Scale text to match the actual container size
        newTextScale *= previewScale;

        const newFontSize = parseInt(this.text.fontSize, 10) * newTextScale;

        this.updateTextSize(newFontSize, previewScale);
      },
      /**
       * Update the font size and position of the text element in the DOM
       */
      updateTextSize(newFontSize, previewScale) {
        if (!this.$refs.previewText) return;

        this.$refs.previewText.style['font-size'] = `${newFontSize}px`;
        this.text.fontSize = `${newFontSize}px`;

        const size = this.getElementSizes();
        this.text.left = '20px';
        this.text.top = `${(this.previewVertOffset * previewScale) + (this.previewHeight - size.text.height) / 2}px`;
      },
      /**
       * Get the full width of the previewText element
       */
      textWidth() {
        // if (!this.$refs.previewText) return;

        this.text.fontSize = `${this.originalFontSize}px`;
        this.$refs.previewText.style['font-size'] = `${this.originalFontSize}px`;
        this.$refs.previewText.style.overflow = 'auto';


        this.$refs.previewText.style.overflow = 'hidden';
        return this.$refs.previewText.scrollWidth;
      },
      /**
       * Get the total height and width for the container and text elements
       */
      getElementSizes() {
        return {
          container: {
            width: this.$el.getBoundingClientRect().width,
            height: this.$el.getBoundingClientRect().height,
          },
          text: {
            width: this.$refs.previewText.getBoundingClientRect().width,
            height: this.$refs.previewText.getBoundingClientRect().height,
          },
        };
      },
      htmlEntities(str) {
        return String(str).replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>')
          .replace(/"/g, '"');
      },
      nl2br(string) {
        return string.replace(/(\r\n|\n\r|\r|\n)/g, '<br>');
      },
      formatScore(score) {
        return score.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
      },
    },
  };
</script>
