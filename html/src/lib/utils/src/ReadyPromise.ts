/**
 * ReadyPromise class extends the native Promise class to add additional functionality.
 * It tracks the pending state of the promise and provides custom resolve and reject methods.
 */
export class ReadyPromise {
  #promise: Promise<void>;

  /**
   * Indicates whether the promise is still pending.
   */
  #isPending: boolean = true;

  /**
   * Custom resolve method for the promise.
   */
  resolve: () => void;

  /**
   * Custom reject method for the promise.
   */
  reject: (reason?: unknown) => void;

  /**
   * Creates an instance of ReadyPromise.
   * @param executor - The executor function for the promise.
   */
  constructor() {
    let originalResolve: () => void;
    let originalReject: (reason: unknown) => void;

    this.#promise = new Promise<void>((resolve, reject) => {
      originalResolve = resolve;
      originalReject = reject;
    });

    this.resolve = () => {
      originalResolve();
      this.#isPending = false;
    };

    this.reject = (reason) => {
      originalReject(reason);
      this.#isPending = false;
    };
  }

  get promise() {
    return this.#promise;
  }

  get isPending() {
    return this.#isPending;
  }
}
