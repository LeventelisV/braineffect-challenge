(function () {
  const scrollable = document.documentElement.scrollHeight - window.innerHeight;
  const endReadingElement = document.getElementById("end-time");
  const startReadingElement = document.getElementById("start-time");
  const progressBarElement = document.querySelector("#progress-bar");
  const totalTimeElement = document.getElementById("total-reading");
  
  let startReadingTime;
  let endReadingTime;
  let scrolling = false;

  setInterval(function () {  
// run every 300 ms, to avoid calling the api in every minor scroll event
    if (scrolling) {
      scrolling = false;
      const { scrollTop } = document.documentElement;
      const scrollPercent = (scrollTop / scrollable) * 100;
      progressBarElement.style.width = scrollPercent + "%";
      if (scrollPercent > 95) {
        fetchAndDisplayEndReadingTime();
        document.getElementById("end-time-container").classList.remove("hidden");
        document.getElementById("total-reading-container").classList.remove("hidden");
      }
    }
  }, 300);

  const fetchAndDisplayStartReadingTime = () => {
    fetch("http://localhost:8087/api/reading/start")
      .then((res) => {
        if (res.ok) {
          return res.json();
        } else {
          return Promise.reject(res);
        }
      })
      .then((data) => {
        startReadingTime = data.startReadingTime;
        if (startReadingElement) {
          startReadingElement.innerText = `${data.startReadingTime.slice(11)}`;
        }
      })
      .catch(function (err) {
        console.error(
          "An error occured while fetching start reading time: ",
          err
        );
      });
  };
  const fetchAndDisplayEndReadingTime = () => {
    fetch("http://localhost:8087/api/reading/end")
      .then((res) => {
        if (res.ok) {
          return res.json();
        } else {
          return Promise.reject(res);
        }
      })
      .then((data) => {
        endReadingTime = data.endReadingTime;
        if (endReadingElement) {
          endReadingElement.innerText = `${data.endReadingTime.slice(11)}`;
        }
      })
      .catch(function (err) {
        console.error(
          "An error occured while fetching end reading time:  ",
          err
        );
      });
  };

  const showTotalReadingTime = (startReadingTime, endReadingTime) => {
    let milliseconds = new Date(endReadingTime) - new Date(startReadingTime);
    let seconds = Math.floor(milliseconds / 1000);
    let minutes = Math.floor(seconds / 60);
    let hours = Math.floor(minutes / 60);
    let days = Math.floor(hours / 24);

    seconds = seconds % 60;
    minutes = minutes % 60;
    hours = hours % 24;
    days = days % 30;
    hours = hours + days * 24;
    totalTimeElement.innerText = `${hours ? hours + " hours," : ""} ${
      minutes ? minutes + " min," : ""
    } ${seconds} sec`;
  };

  fetchAndDisplayStartReadingTime();

  //Javascript to toggle the menu
  document.getElementById("nav-toggle").onclick = function (e) {
    document.getElementById("nav-content").classList.toggle("hidden");
  };

  // the observer observes when we have finished the post and runs the method to calculate the total time
  const mutationObserver = new MutationObserver(() => {
    showTotalReadingTime(startReadingTime, endReadingTime);
  });

  mutationObserver.observe(endReadingElement, { childList: true });

  //retrieve the reading progress and scroll there
  document.addEventListener("DOMContentLoaded", function (e) {
    let prevScrollPos = localStorage.getItem("scrollpos");
    if (prevScrollPos) window.scrollTo(0, prevScrollPos);
  });

  //store the reading progress
  window.onbeforeunload = function (e) {
    localStorage.setItem("scrollpos", window.scrollY);
  };

  document.addEventListener("scroll", function (e) {
    scrolling = true;
  });
})();
