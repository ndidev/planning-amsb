/**
 * Erreurs HTTP.
 */
export namespace HTTP {
  /**
   * Lance une erreur en fonction de la réponse HTTP.
   *
   * @param reponse Réponse HTTP
   *
   * @memberof HTTP
   */
  export async function throwError(response: Response) {
    const message =
      (await response.text()) || `${response.status}: ${response.statusText}`;

    switch (response.status) {
      case 400:
        throw new HTTP.BadRequest(response, message);

      case 401:
        throw new HTTP.Unauthorized(response, message);

      case 403:
        throw new HTTP.Forbidden(response, message);

      case 404:
        throw new HTTP.NotFound(response, message);

      case 500:
        throw new HTTP.InternalServerError(response, message);

      default:
        switch (true) {
          case response.status >= 400 && response.status <= 499:
            throw new HTTP.ClientError(response, message);

          case response.status >= 500:
            throw new HTTP.ServerError(response, message);

          default:
            throw new HTTP.ResponseError(response, message);
        }
    }
  }

  /** ================================ */

  export type Error =
    | ResponseError
    | ClientError
    | BadRequest
    | Unauthorized
    | Forbidden
    | NotFound
    | ServerError
    | InternalServerError;

  /**
   * Classe générique d'erreur pour les réponses HTTP 4XX/5XX.
   *
   * @memberof HTTP
   */
  export class ResponseError extends Error {
    /**
     * Code du statut de la réponse HTTP.
     */
    status: number;

    /**
     * Texte du statut de la réponse HTTP.
     */
    statusText: string;

    constructor(response: Response, message: string) {
      super();
      this.status = response.status;
      this.statusText = response.statusText;
      this.message = message.replace(/\n|\r\n/g, "<br/>");
    }
  }

  /** ================================ */

  /**
   * Erreur lancée lors d'une erreur client.
   *
   * Erreurs HTTP 4XX.
   *
   * @memberof HTTP
   */
  export class ClientError extends ResponseError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }

  /**
   * Erreur HTTP 400 Bad Request.
   *
   * @memberof HTTP
   */
  export class BadRequest extends ClientError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }

  /**
   * Erreur HTTP 401 Unauthorized.
   *
   * @memberof HTTP
   */
  export class Unauthorized extends ClientError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }

  /**
   * Erreur HTTP 403 Forbidden.
   *
   * @memberof HTTP
   */
  export class Forbidden extends ClientError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }

  /**
   * Erreur HTTP 404 Not Found.
   *
   * @memberof HTTP
   */
  export class NotFound extends ClientError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }

  /** ================================ */

  /**
   * Erreur lancée lors d'une erreur serveur.
   *
   * Erreurs HTTP 5XX.
   *
   * @memberof HTTP
   */
  export class ServerError extends ResponseError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }

  /**
   * Erreur HTTP 500 Internal Server Error.
   *
   * @memberof HTTP
   */
  export class InternalServerError extends ServerError {
    constructor(response: Response, message: string) {
      super(response, message);
    }
  }
}
