import { env } from "@app/utils";

(async () => {
  const url = new URL(env.auth);
  url.pathname += "check";

  const params = {
    rubrique: window.location.pathname.split("/")[1],
  };

  url.search = new URLSearchParams(params).toString();

  const reponse = await fetch(url);

  switch (reponse.status) {
    case 200:
      const user = await reponse.json();
      localStorage.setItem("stores/user", JSON.stringify(user));
      break;

    case 401:
    case 403:
      location.pathname = env.prefix + "/index.html";
      break;

    default:
      break;
  }
})();
